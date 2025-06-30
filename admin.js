// 管理面板入口文件
import app from 'flarum/admin/app';
import initialize from './js/src/admin';

app.initializers.add('leot-ai-support-widget', initialize); 