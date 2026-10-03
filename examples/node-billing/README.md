# node-billing

A billing service written in Node, taking part in the skeleton's modules without any PHP. For each
user iam registers, it asks iam the user's name over RPC, opens an account and announces
`billing.account.opened`. It speaks what the package's `docs/other-languages.md` describes:

| | How |
|---|---|
| reads the modules' events | its own consumer group, `billing`, on the stream |
| calls iam | `POST /iam/rpc/findUser`, signed with the shared RPC secret |
| announces | `XADD` of an envelope, with `billing` as emitter |

```bash
npm install
REDIS_URL=redis://127.0.0.1:6379 \
MODULITH_STREAM_KEY=laravel-database-modulith:events \
MODULITH_IAM_HOST=http://127.0.0.1:8000 \
MODULITH_RPC_SECRET=<the modules' MODULITH_RPC_SECRET or APP_KEY> \
npm start              # --once: stop after the first registration
```

`MODULITH_STREAM_KEY` is the key Redis holds, with the prefix of the modules' Redis connection
(`laravel-database-` unless `REDIS_PREFIX` says otherwise).

For a PHP module to handle `billing.account.opened`, declare `billing` in `config/modulith.php`
with its host, and list the handler under `events.listen`: a consumer skips an envelope whose
emitter is not a declared module.
