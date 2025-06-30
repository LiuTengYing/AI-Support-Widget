import app from 'flarum/admin/app';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import Switch from 'flarum/common/components/Switch';
import KnowledgeBaseSettingsModal from './KnowledgeBaseSettingsModal';
import AiUsageStatsModal from './AiUsageStatsModal';

export default class AiSettingsPage extends ExtensionPage {
  oninit(vnode) {
    super.oninit(vnode);
  }

  content() {
    return (
      <div className="AiSettingsPage">
        <div className="container">
          {this.buildSettingsForm()}
        </div>
      </div>
    );
  }

  buildSettingsForm() {
    return (
      <div className="Form">
        <h2>AI Support Widget Settings</h2>
        
        <div className="Form-group">
          <label>Enable AI Support Widget</label>
          <Switch
            state={this.setting('leot-ai-support-widget.enabled')()}
            onchange={this.setting('leot-ai-support-widget.enabled')}
          >
            Enable AI Support Widget
          </Switch>
          <div className="helpText">
            Turn on or off the AI support widget on your forum.
          </div>
        </div>
        
        <div className="Form-group">
          <label>AI Provider</label>
          <div className="Select">
            <select 
              className="FormControl"
              value={this.setting('leot-ai-support-widget.provider')()} 
              onchange={e => this.setting('leot-ai-support-widget.provider')(e.target.value)}
            >
              <option value="openai">OpenAI ChatGPT</option>
              <option value="deepseek">DeepSeek</option>
              <option value="claude">Claude</option>
              <option value="gemini">Gemini</option>
            </select>
          </div>
          <div className="helpText">
            Select which AI provider to use for the support widget.
          </div>
        </div>
        
        <div className="Form-group">
          <label>API Key</label>
          <div className="FormControl-container">
            <input 
              className="FormControl"
              type="password" 
              bidi={this.setting('leot-ai-support-widget.api_key')}
            />
          </div>
          <div className="helpText">
            Enter your API key for the selected AI provider.
          </div>
        </div>
        
        <h3>Proxy Settings</h3>
        
        <div className="Form-group">
          <Switch
            state={this.setting('leot-ai-support-widget.openai_use_proxy')()}
            onchange={this.setting('leot-ai-support-widget.openai_use_proxy')}
          >
            Enable Proxy for OpenAI
          </Switch>
          <div className="helpText">
            Enable proxy connection for OpenAI API requests.
          </div>
        </div>
        
        <div className="Form-group">
          <label>Proxy Host</label>
          <div className="FormControl-container">
            <input 
              className="FormControl"
              type="text" 
              placeholder="127.0.0.1"
              bidi={this.setting('leot-ai-support-widget.proxy_host')}
            />
          </div>
          <div className="helpText">
            Proxy server host address (default: 127.0.0.1).
          </div>
        </div>
        
        <div className="Form-group">
          <label>Proxy Port</label>
          <div className="FormControl-container">
            <input 
              className="FormControl"
              type="number" 
              min="1" 
              max="65535" 
              placeholder="7890"
              bidi={this.setting('leot-ai-support-widget.proxy_port')}
            />
          </div>
          <div className="helpText">
            Proxy server port number (default: 7890).
          </div>
        </div>
        
        <div className="Form-group">
          <label>Model Name</label>
          <div className="FormControl-container">
            <input 
              className="FormControl"
              type="text" 
              placeholder="gpt-3.5-turbo, deepseek-chat..."
              bidi={this.setting('leot-ai-support-widget.model_name')}
            />
          </div>
          <div className="helpText">
            Enter the model name to use (e.g., gpt-3.5-turbo, deepseek-chat).
          </div>
        </div>
        
        <div className="Form-group">
          <label>Max Tokens</label>
          <div className="FormControl-container">
            <input 
              className="FormControl"
              type="number" 
              min="100" 
              max="4000" 
              bidi={this.setting('leot-ai-support-widget.max_tokens')}
            />
          </div>
          <div className="helpText">
            Maximum number of tokens to generate in AI responses.
          </div>
        </div>
        
        <div className="Form-group">
          <label>Request Timeout</label>
          <div className="FormControl-container">
            <input 
              className="FormControl"
              type="number" 
              min="5" 
              max="120" 
              bidi={this.setting('leot-ai-support-widget.timeout')}
            />
          </div>
          <div className="helpText">
            Timeout in seconds for AI API requests.
          </div>
        </div>
        
        <h3>Usage Limit Settings</h3>
        
        <div className="Form-group">
          <label>Daily Requests Limit</label>
          <div className="FormControl-container">
            <input 
              className="FormControl"
              type="number" 
              min="1" 
              bidi={this.setting('leot-ai-support-widget.daily_requests_limit')}
            />
          </div>
          <div className="helpText">
            Maximum number of AI requests per user per day.
          </div>
        </div>
        
        <div className="Form-group">
          <label>Usage Statistics</label>
          <button 
            className="Button" 
            type="button"
            onclick={() => app.modal.show(AiUsageStatsModal)}
          >
            View Usage Statistics
          </button>
          <div className="helpText">
            View detailed statistics about AI usage by users.
          </div>
        </div>
        
        <h3>Content Settings</h3>
        
        <div className="Form-group">
          <Switch
            state={this.setting('leot-ai-support-widget.enable_indexing')()}
            onchange={this.setting('leot-ai-support-widget.enable_indexing')}
          >
            Enable Forum Content Indexing
          </Switch>
          <div className="helpText">
            Allow AI to search and reference forum content.
          </div>
        </div>
        
        <div className="Form-group">
          <label>Search Result Limit</label>
          <div className="FormControl-container">
            <input 
              className="FormControl"
              type="number" 
              min="1" 
              max="20" 
              bidi={this.setting('leot-ai-support-widget.search_limit')}
            />
          </div>
          <div className="helpText">
            Maximum number of search results to include in AI context.
          </div>
        </div>
        
        <h3>Knowledge Base Settings</h3>
        
        <div className="Form-group">
          <Switch
            state={this.setting('leot-ai-support-widget.kb_enabled')()}
            onchange={this.setting('leot-ai-support-widget.kb_enabled')}
          >
            Enable Knowledge Base
          </Switch>
          <div className="helpText">
            Enable knowledge base to provide reference information for AI responses.
          </div>
        </div>
        
        <div className="Form-group">
          <label>Knowledge Base Search Weight</label>
          <div className="FormControl-container">
            <input 
              className="FormControl"
              type="number" 
              step="0.1" 
              min="0.1" 
              max="5" 
              bidi={this.setting('leot-ai-support-widget.kb_search_weight')}
            />
          </div>
          <div className="helpText">
            Higher values prioritize knowledge base content in AI responses.
          </div>
        </div>
        
        <div className="Form-group">
          <button 
            className="Button"
            type="button" 
            onclick={() => this.showKnowledgeBaseModal()}
          >
            Manage Knowledge Base Entries
          </button>
          <div className="helpText">
            Add, edit, or remove knowledge base entries for AI assistant.
          </div>
        </div>
        
        <h3>Widget Settings</h3>
        
        <div className="Form-group">
          <label>Widget Position</label>
          <div className="Select">
            <select 
              className="FormControl"
              value={this.setting('leot-ai-support-widget.widget_position')()} 
              onchange={e => this.setting('leot-ai-support-widget.widget_position')(e.target.value)}
            >
              <option value="bottom-right">Bottom Right</option>
              <option value="bottom-left">Bottom Left</option>
            </select>
          </div>
          <div className="helpText">
            Choose where the AI support widget should appear on the page.
          </div>
        </div>
        
        <div className="Form-group">
          <label>Theme</label>
          <div className="Select">
            <select 
              className="FormControl"
              value={this.setting('leot-ai-support-widget.theme')()} 
              onchange={e => this.setting('leot-ai-support-widget.theme')(e.target.value)}
            >
              <option value="light">Light</option>
              <option value="dark">Dark</option>
              <option value="auto">Auto (Follow System)</option>
            </select>
          </div>
          <div className="helpText">
            Choose the theme for the AI support widget.
          </div>
        </div>
      
        {this.submitButton()}
      </div>
    );
  }

  showKnowledgeBaseModal() {
    app.modal.show(KnowledgeBaseSettingsModal);
  }
}