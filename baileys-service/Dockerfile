FROM node:20-alpine

WORKDIR /app

RUN apk add --no-cache git

COPY package.json package-lock.json ./
RUN npm ci --omit=dev --legacy-peer-deps

COPY index.js ./

CMD ["node", "index.js"]
