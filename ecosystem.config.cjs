/* eslint-env node */
module.exports = {
    apps: [
        {
            name: "laravel-node-server",
            script: "npm",
            args: "run node-server",
            cwd: "./",
            env: {
                NODE_ENV: "production"
            }
        }
    ]
};
