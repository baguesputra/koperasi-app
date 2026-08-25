FROM node:20-alpine

WORKDIR /app

COPY package.json ./
RUN apk add --no-cache git && npm install --omit=dev --legacy-peer-deps

COPY index.js ./

CMD ["node", "index.js"]
