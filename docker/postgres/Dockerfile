# The init script is baked in, not bind-mounted: on macOS a mounted script fails to run ("Permission denied").
FROM postgres:17-alpine

COPY init/ /docker-entrypoint-initdb.d/
