# 0007: Elasticsearch and Kibana in Docker for search practice

- **Status:** Accepted
- **Date:** 2026-11-17

## Context

The project owner wants to practise Elasticsearch on the 10,000,000-row `products` table (Catalog module). For now the request is infrastructure only: no search API, no indexing code and no queries.

## Options

- **Where to run it:**
  - Docker Compose services next to the existing stack: same on every PC, one command.
  - A local Windows install: per-machine setup, harder to reproduce.
  - Elastic Cloud: costs money and needs an account and network access.
- **Security:**
  - Security off (`xpack.security.enabled=false`): no passwords or certificates, which suits a single local machine. This is Elastic's own local-development setup.
  - Security on: closer to production, but needs certificate and password setup before anything works.
- **PHP client:** `elasticsearch/elasticsearch` or plain HTTP. Not decided yet; a new package needs the owner's approval.

## Decision

- `compose.yaml` adds two services:
  - `elasticsearch`: the official image, 9.1.5 by default (`ELASTIC_VERSION`), single node, security off, 1 GB heap (`DOCKER_ES_HEAP`), data in the `es-data` volume, with a health check on cluster health.
  - `kibana`: the same version, for the Dev Tools console. It starts once Elasticsearch is healthy.
- Both ports are published on **127.0.0.1 only** (9200 and 5601), so the unsecured cluster is not reachable from the network.
- The app does not depend on Elasticsearch: the API and seeding start without it.
- Laravel reads the address from `config('services.elasticsearch.host')`, set by `ELASTICSEARCH_HOST`: `http://localhost:9200` on the host, `http://elasticsearch:9200` inside the app container.

## Reason

Docker gives the same setup on any PC with a single command. Turning security off and binding to localhost keeps the setup simple without exposing the cluster.

## Consequences

- Elasticsearch plus Kibana need about 2-3 GB of RAM on top of MySQL. Start them only when needed: `docker compose up -d elasticsearch kibana`.
- On Linux, the host needs `vm.max_map_count=262144`. Docker Desktop on Windows and macOS already sets it.
- A single node cannot place replicas, so indexes created with 1 replica show **yellow** health. Create practice indexes with `number_of_replicas: 0` to get green.
- This setup is **never** for production: there, security must be on (TLS and authentication).
- Next decisions: the PHP client, how products are indexed (bulk import, then incremental sync using `updated_at`), and the index mapping.
