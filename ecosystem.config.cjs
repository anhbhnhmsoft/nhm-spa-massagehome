/* eslint-env node */
module.exports = {
    apps: [
        {
            name: "laravel-node-server",
            script: "./node_modules/tsx/dist/cli.mjs",
            args: "./node_server/index.ts",
            interpreter: "node",
            cwd: "./",
            env: {
                NODE_ENV: "production",
                TS_NODE_PROJECT: "./node_server/tsconfig.json"
            }
        }
    ]
};
