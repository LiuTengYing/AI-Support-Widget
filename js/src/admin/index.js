import app from 'flarum/admin/app';
import KnowledgeBaseSettingsModal from './components/KnowledgeBaseSettingsModal';
import AiSettingsPage from './components/AiSettingsPage';
import AiUsageStatsModal from './components/AiUsageStatsModal';

// Export the initialization function
export default function() {
  // 注册设置项
  app.extensionData
    .for('leot-ai-support-widget')
    // 基本设置
    .registerSetting({
      setting: 'leot-ai-support-widget.enabled',
      type: 'boolean',
      label: 'Enable AI Support Widget',
      default: true
    })
    .registerSetting({
      setting: 'leot-ai-support-widget.provider',
      type: 'select',
      label: 'AI Provider',
      options: {
        'openai': 'OpenAI ChatGPT',
        'deepseek': 'DeepSeek',
        'claude': 'Claude',
        'gemini': 'Gemini'
      },
      default: 'openai'
    })
    .registerSetting({
      setting: 'leot-ai-support-widget.api_key',
      type: 'text',
      label: 'API Key',
    })
    .registerSetting({
      setting: 'leot-ai-support-widget.model_name',
      type: 'text',
      label: 'Model Name',
      default: 'gpt-3.5-turbo',
      placeholder: 'gpt-3.5-turbo, deepseek-chat...'
    })
    .registerSetting({
      setting: 'leot-ai-support-widget.max_tokens',
      type: 'number',
      label: 'Max Tokens',
      default: 500,
      min: 100,
      max: 4000
    })
    .registerSetting({
      setting: 'leot-ai-support-widget.timeout',
      type: 'number',
      label: 'Request Timeout',
      help: 'Timeout in seconds for AI API requests',
      default: 15,
      min: 5,
      max: 120
    })
    // 使用限制设置
    .registerSetting(() => <h3>Usage Limit Settings</h3>)
    .registerSetting({
      setting: 'leot-ai-support-widget.daily_requests_limit',
      type: 'number',
      label: 'Daily Requests Limit',
      help: 'Maximum number of AI requests per user per day',
      default: 20,
      min: 1,
      onchange: function(value) {
        let numValue = parseInt(value, 10);
        if (isNaN(numValue) || numValue < 1) {
          app.alerts.show({type: 'error'}, 'Daily request limit must be at least 1');
          return 1;
        }
        return numValue;
      }
    })
    .registerSetting({
      setting: 'leot-ai-support-widget.usage_stats',
      type: 'custom',
      component: function() {
        return (
          <div className="Form-group">
            <label>Usage Statistics</label>
            <button 
              className="Button" 
              onclick={() => app.modal.show(AiUsageStatsModal)}
            >
              View Usage Statistics
            </button>
            <div className="helpText">
              View detailed statistics about AI usage by users
            </div>
          </div>
        );
      }
    })
    // 内容索引设置
    .registerSetting(() => <h3>Content Settings</h3>)
    .registerSetting({
      setting: 'leot-ai-support-widget.enable_indexing',
      type: 'boolean',
      label: 'Enable Forum Content Indexing',
      help: 'Allow AI to search and reference forum content',
      default: true
    })
    .registerSetting({
      setting: 'leot-ai-support-widget.search_limit',
      type: 'number',
      label: 'Search Result Limit',
      help: 'Maximum number of search results to include in AI context',
      default: 10,
      min: 1,
      max: 20
    })
    // 知识库设置 - 只保留启用开关和按钮
    .registerSetting(() => <h3>Knowledge Base Settings</h3>)
    .registerSetting({
      setting: 'leot-ai-support-widget.kb_enabled',
      type: 'boolean',
      label: 'Enable Knowledge Base',
      default: true
    })
    .registerSetting({
      setting: 'leot-ai-support-widget.kb_search_weight',
      type: 'number',
      label: 'Knowledge Base Search Weight',
      help: 'Higher values prioritize knowledge base content in AI responses',
      default: 1.5,
      min: 0.1,
      max: 5,
      step: 0.1
    })
    .registerSetting({
      label: 'Knowledge Base Management',
      setting: 'leot-ai-support-widget.kb_management',
      type: 'custom',
      component: function() {
        return (
          <div className="Form-group">
            <button 
              className="Button" 
              onclick={() => app.modal.show(KnowledgeBaseSettingsModal)}
            >
              Manage Knowledge Base Entries
            </button>
            <div className="helpText">
              Add, edit, or remove knowledge base entries for AI assistant
            </div>
          </div>
        );
      }
    })
    // 小部件设置
    .registerSetting(() => <h3>Widget Settings</h3>)
    .registerSetting({
      setting: 'leot-ai-support-widget.widget_position',
      type: 'select',
      label: 'Widget Position',
      options: {
        'bottom-right': 'Bottom Right',
        'bottom-left': 'Bottom Left'
      },
      default: 'bottom-right'
    })
    .registerSetting({
      setting: 'leot-ai-support-widget.theme',
      type: 'select',
      label: 'Theme',
      options: {
        'light': 'Light',
        'dark': 'Dark',
        'auto': 'Auto (Follow System)'
      },
      default: 'auto'
    })
    // 权限设置
    .registerPermission({
      icon: 'fas fa-robot',
      label: 'Use AI Support',
      permission: 'leot-ai-support-widget.use',
      allowGuest: false
    }, 'start')
    .registerPermission({
      icon: 'fas fa-database',
      label: 'Manage knowledge base',
      permission: 'leot-ai-support-widget.manageKnowledgeBase',
      allowGuest: false
    }, 'moderate');

  // 添加AI设置页面为主页面
  app.extensionData
    .for('leot-ai-support-widget')
    .registerPage(AiSettingsPage);
}