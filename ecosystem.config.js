module.exports = {
  apps: [
    {
      name: 'clearwater-frontend',
      cwd: '/root/.openclaw/workspace/Clearwater/frontend',
      script: 'npx',
      args: 'next dev -p 3000 -H 0.0.0.0',
      restart_delay: 3000,
      max_restarts: 10,
      env: {
        NODE_ENV: 'development'
      }
    },
    {
      name: 'clearwater-backend',
      cwd: '/root/.openclaw/workspace/Clearwater/backend',
      script: 'php',
      args: 'artisan serve --host=127.0.0.1 --port=8000',
      restart_delay: 3000,
      max_restarts: 10
    }
  ]
};
