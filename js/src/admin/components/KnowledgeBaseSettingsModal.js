import app from 'flarum/admin/app';
import Modal from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import DeleteKnowledgeBaseEntryModal from './DeleteKnowledgeBaseEntryModal';
import EditKnowledgeBaseEntryModal from './EditKnowledgeBaseEntryModal';

/**
 * Knowledge Base Management Modal Component
 */
export default class KnowledgeBaseSettingsModal extends Modal {
  oninit(vnode) {
    super.oninit(vnode);
    
    this.loading = true;
    this.entries = [];
    this.categories = [];
    
    // Form data
    this.newEntry = {
      type: 'qa',
      question: '',
      answer: '',
      keywords: '',
      category_id: null
    };
    
    this.newCategory = {
      name: '',
      description: ''
    };
    
    // Load knowledge base entries
    this.loadEntries();
    this.loadCategories();
  }
  
  className() {
    return 'KnowledgeBaseSettingsModal Modal--large';
  }
  
  title() {
    return 'Knowledge Base Management';
  }
  
  loadEntries() {
    this.loading = true;
    app.request({
      method: 'GET',
      url: app.forum.attribute('apiUrl') + '/ai-support/kb'
    }).then(result => {
      this.entries = result.data || [];
      this.loading = false;
      m.redraw();
    }).catch(error => {
      this.loading = false;
      app.alerts.show({type: 'error'}, 'Failed to load knowledge base entries: ' + error.message);
      m.redraw();
    });
  }
  
  loadCategories() {
    app.request({
      method: 'GET',
      url: app.forum.attribute('apiUrl') + '/ai-support/kb/categories'
    }).then(result => {
      this.categories = result.data || [];
      m.redraw();
    }).catch(error => {
      app.alerts.show({type: 'error'}, 'Failed to load knowledge base categories: ' + error.message);
    });
  }
  
  saveEntry() {
    if (!this.validateEntry()) {
      return;
    }
    
    this.loading = true;
    
    app.request({
      method: 'POST',
      url: app.forum.attribute('apiUrl') + '/ai-support/kb',
      body: {data: {attributes: this.newEntry}}
    }).then((result) => {
      // Reset form
      this.newEntry = {
        type: 'qa',
        question: '',
        answer: '',
        keywords: '',
        category_id: null
      };
      
      // Reload entries
      this.loadEntries();
      
      app.alerts.show({type: 'success'}, 'Knowledge base entry has been added');
    }).catch(error => {
      this.loading = false;
      app.alerts.show({type: 'error'}, 'Failed to add knowledge base entry: ' + error.message);
      m.redraw();
    });
  }
  
  validateEntry() {
    if (this.newEntry.type === 'qa' && !this.newEntry.question) {
      app.alerts.show({type: 'error'}, 'Question is required for Q&A type entries');
      return false;
    }
    
    if (!this.newEntry.answer) {
      app.alerts.show({type: 'error'}, 'Answer/content cannot be empty');
      return false;
    }
    
    return true;
  }
  
  // 获取条目的类型
  getEntryType(entry) {
    // 首先尝试从attributes中获取type
    if (entry.attributes && entry.attributes.type) {
      return entry.attributes.type;
    }
    
    // 如果没有attributes或attributes中没有type，则尝试直接获取
    if (entry.type && entry.type !== 'kb-entries') {
      return entry.type;
    }
    
    // 默认返回qa类型
    return 'qa';
  }
  
  // 获取条目的问题/标题
  getEntryTitle(entry) {
    // 获取条目类型
    const type = this.getEntryType(entry);
    
    if (type === 'qa') {
      return entry.question || entry.attributes?.question || '(No question)';
    } else {
      // 对于Content类型，尝试使用question字段作为标题
      const title = entry.question || entry.attributes?.question;
      
      if (title) {
        return title;
      }
      
      // 如果没有标题，尝试从内容生成标题
      const content = this.getEntryContent(entry);
      if (content && content !== '(No content)') {
        // 从内容中提取前30个字符作为标题
        const generatedTitle = content.substring(0, 30) + (content.length > 30 ? '...' : '');
        
        // 如果有关键词，使用第一个关键词作为标题前缀
        const keywords = entry.keywords || entry.attributes?.keywords;
        if (keywords) {
          const firstKeyword = keywords.split(',')[0].trim();
          if (firstKeyword) {
            return firstKeyword + ': ' + generatedTitle;
          }
        }
        
        return generatedTitle;
      }
      
      return '(No title)';
    }
  }
  
  // 获取条目的答案/内容
  getEntryContent(entry) {
    return entry.answer || entry.attributes?.answer || entry.content || entry.attributes?.content || '(No content)';
  }
  
  // 获取条目的关键词
  getEntryKeywords(entry) {
    return entry.keywords || entry.attributes?.keywords || '(None)';
  }
  
  editEntry(entry) {
    app.modal.show(EditKnowledgeBaseEntryModal, {
      entry: entry,
      categories: this.categories,
      onsubmit: (editedEntry) => {
        this.loading = true;
        
        app.request({
          method: 'PATCH',
          url: `${app.forum.attribute('apiUrl')}/ai-support/kb/${entry.id}`,
          body: {data: {attributes: editedEntry}}
        }).then(result => {
          // Update local entry
          const index = this.entries.findIndex(e => e.id === entry.id);
          if (index !== -1) {
            this.entries[index] = result.data;
          }
          
          this.loading = false;
          app.alerts.show({type: 'success'}, 'Knowledge base entry has been updated');
          m.redraw();
        }).catch(error => {
          this.loading = false;
          app.alerts.show({type: 'error'}, 'Failed to update knowledge base entry: ' + error.message);
          m.redraw();
        });
      }
    });
  }
  
  deleteEntry(entry) {
    app.modal.show(DeleteKnowledgeBaseEntryModal, {
      entry: entry,
      onconfirm: () => {
        this.loading = true;
        
        app.request({
          method: 'DELETE',
          url: `${app.forum.attribute('apiUrl')}/ai-support/kb/${entry.id}`
        }).then(() => {
          this.entries = this.entries.filter(e => e.id !== entry.id);
          this.loading = false;
          app.alerts.show({type: 'success'}, 'Knowledge base entry has been deleted');
          m.redraw();
        }).catch(error => {
          this.loading = false;
          app.alerts.show({type: 'error'}, 'Failed to delete knowledge base entry: ' + error.message);
          m.redraw();
        });
      }
    });
  }
  
  content() {
    return (
      <div className="Modal-body">
        <div className="Form">
          <div className="Form-group">
            <h3>Add Knowledge Base Entry</h3>
            
            <div className="Form-group">
              <label>Type</label>
              <div className="Select">
                <select
                  className="FormControl"
                  value={this.newEntry.type}
                  onchange={(e) => {
                    this.newEntry.type = e.target.value;
                    m.redraw();
                  }}
                >
                  <option value="qa">Q&A Type</option>
                  <option value="content">Content Type</option>
                </select>
              </div>
            </div>
            
            {this.newEntry.type === 'qa' && (
              <div className="Form-group">
                <label>Question</label>
                <div className="FormControl-container">
                  <input
                    className="FormControl"
                    type="text"
                    value={this.newEntry.question}
                    oninput={(e) => {
                      this.newEntry.question = e.target.value;
                    }}
                  />
                </div>
              </div>
            )}
            
            {this.newEntry.type === 'content' && (
              <div className="Form-group">
                <label>Title (Optional)</label>
                <div className="FormControl-container">
                  <input
                    className="FormControl"
                    type="text"
                    value={this.newEntry.question}
                    placeholder="Optional title for content entry"
                    oninput={(e) => {
                      this.newEntry.question = e.target.value;
                    }}
                  />
                </div>
                <div className="helpText">
                  Adding a title helps the AI match relevant content more accurately
                </div>
              </div>
            )}
            
            <div className="Form-group">
              <label>{this.newEntry.type === 'qa' ? 'Answer' : 'Content'}</label>
              <div className="FormControl-container">
                <textarea
                  className="FormControl"
                  rows="5"
                  value={this.newEntry.answer}
                  oninput={(e) => {
                    this.newEntry.answer = e.target.value;
                  }}
                />
              </div>
            </div>
            
            <div className="Form-group">
              <label>Keywords (comma separated)</label>
              <div className="FormControl-container">
                <input
                  className="FormControl"
                  type="text"
                  value={this.newEntry.keywords}
                  oninput={(e) => {
                    this.newEntry.keywords = e.target.value;
                  }}
                />
              </div>
              <div className="helpText">
                Adding keywords helps the AI match relevant content more accurately
              </div>
            </div>
            
            {this.categories.length > 0 && (
              <div className="Form-group">
                <label>Category</label>
                <div className="Select">
                  <select
                    className="FormControl"
                    value={this.newEntry.category_id}
                    onchange={(e) => {
                      this.newEntry.category_id = e.target.value ? parseInt(e.target.value) : null;
                    }}
                  >
                    <option value="">-- Select Category --</option>
                    {this.categories.map(category => (
                      <option value={category.id} key={category.id}>
                        {category.name}
                      </option>
                    ))}
                  </select>
                </div>
              </div>
            )}
            
            <div className="Form-group">
              <Button
                className="Button Button--primary"
                loading={this.loading}
                onclick={() => this.saveEntry()}
              >
                Add Entry
              </Button>
            </div>
          </div>
          
          <div className="Form-group">
            <h3>Knowledge Base Entries</h3>
            
            {this.loading && <LoadingIndicator />}
            
            {!this.loading && this.entries.length === 0 && (
              <div className="helpText">No knowledge base entries found. Add your first entry above.</div>
            )}
            
            {!this.loading && this.entries.length > 0 && (
              <div className="KnowledgeBaseEntries">
                <table className="KnowledgeBaseEntries-table">
                  <thead>
                    <tr>
                      <th>Type</th>
                      <th>Question/Title</th>
                      <th>Answer/Content</th>
                      <th>Keywords</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {this.entries.map(entry => (
                      <tr key={entry.id}>
                        <td>{this.getEntryType(entry) === 'qa' ? 'Q&A' : 'Content'}</td>
                        <td>
                          {this.getEntryTitle(entry)}
                        </td>
                        <td>
                          <div className="KnowledgeBaseEntries-content">
                            {this.getEntryContent(entry)}
                          </div>
                        </td>
                        <td>{this.getEntryKeywords(entry)}</td>
                        <td>
                          <div className="ButtonGroup">
                            <Button
                              className="Button Button--icon"
                              icon="fas fa-edit"
                              onclick={() => this.editEntry(entry)}
                              title="Edit"
                            />
                            <Button
                              className="Button Button--icon"
                              icon="fas fa-trash"
                              onclick={() => this.deleteEntry(entry)}
                              title="Delete"
                            />
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        </div>
      </div>
    );
  }
} 