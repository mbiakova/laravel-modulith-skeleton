// A billing service in Node: it opens an account for each user iam registers, asks iam the user's
// name over RPC, and announces the account. Everything it speaks is in laravel-modulith's
// docs/other-languages.md.
import { createHmac, randomUUID } from 'node:crypto';
import { createClient } from 'redis';

const NAME = 'billing';                                                // its name in modulith.modules
const STREAM = process.env.MODULITH_STREAM_KEY ?? 'modulith:events';
const IAM = process.env.MODULITH_IAM_HOST ?? 'http://iam.svc:8000';
const SECRET = process.env.MODULITH_RPC_SECRET;
const ONCE = process.argv.includes('--once');                          // handle one event, then stop

const redis = createClient({ url: process.env.REDIS_URL ?? 'redis://redis:6379' });
await redis.connect();

// Its own consumer group, from the start of the stream, as a PHP module's.
await redis.xGroupCreate(STREAM, NAME, '0', { MKSTREAM: true }).catch(() => {});

for (;;) {
    const read = await redis.xReadGroup(NAME, `${NAME}:node`, { key: STREAM, id: '>' }, { BLOCK: 5000, COUNT: 10 });

    for (const { id, message } of read?.[0]?.messages ?? []) {
        const event = JSON.parse(message.envelope);

        if (event.name === 'iam.user.registered') {
            const user = await rpc('iam', 'findUser', 'Foundation\\Iam\\Contracts\\IamService', { id: event.payload.id });
            console.log(`account opened for ${user?.name ?? 'an unknown user'} (#${event.payload.id})`);
            await emit('billing.account.opened', { user_id: event.payload.id });
        }

        await redis.xAck(STREAM, NAME, id);                            // once handled, never before

        if (ONCE && event.name === 'iam.user.registered') {
            await redis.quit();
            process.exit(0);
        }
    }
}

async function emit(name, payload) {
    const envelope = {
        id: randomUUID(),
        emitter: NAME,
        name,
        payload,
        headers: {},
        emitted_at: new Date().toISOString(),
        recipients: [],
        stream: 'default',
        version: 1,
    };

    await redis.xAdd(STREAM, '*', { envelope: JSON.stringify(envelope) });
}

async function rpc(module, method, contract, args) {
    const path = `/${module}/rpc/${method}`;
    const body = JSON.stringify({ contract, arguments: args });
    const timestamp = String(Math.floor(Date.now() / 1000));
    const nonce = randomUUID();
    const context = '{}';
    const signature = createHmac('sha256', SECRET).update([timestamp, nonce, path, body, context].join('\n')).digest('hex');

    const response = await fetch(`${IAM}${path}`, {
        method: 'POST',
        body,
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Modulith-Timestamp': timestamp,
            'X-Modulith-Nonce': nonce,
            'X-Modulith-Context': context,
            'X-Modulith-Signature': signature,
        },
    });

    if (response.status === 404) {
        return null;                                                   // the method returned null
    }

    if (!response.ok) {
        throw new Error(`${path} answered ${response.status}: ${await response.text()}`);
    }

    return response.json();
}
