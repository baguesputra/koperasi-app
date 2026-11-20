FROM node:22-alpine

WORKDIR /app

RUN apk add --no-cache git \
    && git config --global url."https://github.com/".insteadOf ssh://git@github.com/ \
    && git config --global url."https://github.com/".insteadOf git://github.com/

COPY package.json package-lock.json ./
RUN npm ci --omit=dev --legacy-peer-deps

COPY index.js ./

EXPOSE 3000

CMD ["node", "index.js"]
