const { defineConfig } = require('@vue/cli-service')
const os = require('os')

// Get local network IP
function getNetworkIP() {
  const interfaces = os.networkInterfaces()
  for (const name of Object.keys(interfaces)) {
    for (const iface of interfaces[name]) {
      // Skip internal (loopback) and non-IPv4 addresses
      if (iface.family === 'IPv4' && !iface.internal) {
        return iface.address
      }
    }
  }
  return 'localhost'
}

module.exports = defineConfig({
  transpileDependencies: true,
  devServer: {
    port: 8080,
    host: '0.0.0.0',
    allowedHosts: 'all',
    client: {
      webSocketURL: {
        hostname: '0.0.0.0',
        pathname: '/ws',
        port: 8080
      }
    },
    onListening: function (devServer) {
      if (!devServer) {
        throw new Error('webpack-dev-server is not defined')
      }
      const networkIP = getNetworkIP()
      const port = devServer.server.address().port
      console.log('\n  App running at:')
      console.log(`  - Local:   http://localhost:${port}/`)
      console.log(`  - Network: http://${networkIP}:${port}/\n`)
    }
  }
})
