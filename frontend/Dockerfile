# React app for Docker: build with Node, serve the static files with nginx.
# nginx also forwards /api/... to the Laravel stack (the "web" service),
# so the browser talks to one origin and no CORS is needed.

FROM node:24-alpine AS build
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-fund --no-audit
COPY . .
RUN npm run build

FROM nginx:1.27-alpine
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/dist /usr/share/nginx/html
