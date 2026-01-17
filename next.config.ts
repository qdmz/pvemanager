import type { NextConfig } from 'next';
import path from 'path';

const nextConfig: NextConfig = {
  outputFileTracingRoot: path.resolve(__dirname),
  /* config options here */
  allowedDevOrigins: ['*.dev.coze.site'],
  images: {
    remotePatterns: [
      {
        protocol: 'https',
        hostname: 'lf-coze-web-cdn.coze.cn',
        pathname: '/**',
      },
    ],
  },
  // 优化构建配置
  webpack: (config, { isServer }) => {
    // 减少构建内存使用
    if (!isServer) {
      config.optimization = {
        ...config.optimization,
        splitChunks: {
          chunks: 'all',
          cacheGroups: {
            default: false,
            vendors: false,
            // 只对 node_modules 进行分包
            node_modules: {
              name: 'vendor',
              chunks: 'all',
              test: /[\\/]node_modules[\\/]/,
              priority: 10,
            },
          },
        },
      };
    }
    return config;
  },
  // 禁用一些可能导致构建失败的功能
  // eslint: {
  //   // 构建时忽略 ESLint 错误
  //   ignoreDuringBuilds: true,
  // },
  // typescript: {
  //   // 构建时忽略 TypeScript 错误
  //   ignoreBuildErrors: false,
  // },
  // 禁用 X-Powered-By 头
  poweredByHeader: false,
  // 优化输出
  compress: true,
  // 启用 SWC 压缩
  swcMinify: true,
} as NextConfig;

export default nextConfig;
