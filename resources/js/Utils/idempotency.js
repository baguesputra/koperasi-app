export function generateIdempotencyKey() {
    return crypto.randomUUID();
}

export function withIdempotencyKey(config = {}) {
    const key = generateIdempotencyKey();
    return {
        ...config,
        headers: {
            ...(config.headers || {}),
            'Idempotency-Key': key,
        },
        onBefore: () => {
            sessionStorage.setItem('lastIdempotencyKey', key);
        },
    };
}