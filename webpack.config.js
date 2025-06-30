const config = require('flarum-webpack-config');
const path = require('path');

// 创建默认配置
const customConfig = config({
  entries: {
    forum: './forum.js',
    admin: './admin.js'
  }
});

// 明确指定输出路径为js/dist
customConfig.output.path = path.resolve(__dirname, 'js/dist');

// 添加sourcemap以便于调试
customConfig.devtool = 'source-map';

// 添加调试信息
console.log('Webpack config entries:', customConfig.entry);
console.log('Webpack config output path:', customConfig.output.path);

module.exports = customConfig;