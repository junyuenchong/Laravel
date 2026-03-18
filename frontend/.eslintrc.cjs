module.exports = {
    root: true,
    env: {
        browser: true,
        es2021: true,
        node: true,
    },
    extends: [
        'eslint:recommended',
        'plugin:vue/vue3-recommended',
        '@vue/eslint-config-typescript',
        // Disable ESLint rules that conflict with Prettier formatting.
        // (Prettier remains a separate step via `npm run format` / `npm run format:fix`.)
        'prettier',
    ],
    parserOptions: {
        ecmaVersion: 'latest',
        sourceType: 'module',
    },
    rules: {
        // Keep code easy to read during interview/demo
        'vue/multi-word-component-names': 'off',
        'no-console': process.env.NODE_ENV === 'production' ? 'warn' : 'off',
        'no-debugger': process.env.NODE_ENV === 'production' ? 'warn' : 'off',
    },
}