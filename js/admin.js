// This is the admin entry point for the extension
import app from 'flarum/admin/app';
import initialize from './src/admin';

app.initializers.add('leot-ai-support-widget', initialize); 