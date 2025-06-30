import app from 'flarum/admin/app';
import Modal from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Dropdown from 'flarum/common/components/Dropdown';
import icon from 'flarum/common/helpers/icon';

/**
 * AI使用统计模态窗口组件
 */
export default class AiUsageStatsModal extends Modal {
  oninit(vnode) {
    super.oninit(vnode);
    
    this.loading = true;
    this.stats = null;
    this.period = 'all';
    this.sortBy = 'total_count';
    this.sortDir = 'desc';
    this.searchQuery = '';
    this.error = null;
    
    // 加载统计数据
    this.loadStats();
  }
  
  className() {
    return 'AiUsageStatsModal Modal--large Modal--dark';
  }
  
  title() {
    return app.translator.trans('leot-ai-support-widget.admin.usage_stats.title');
  }
  
  loadStats() {
    this.loading = true;
    this.error = null;
    
    app.request({
      method: 'GET',
      url: app.forum.attribute('apiUrl') + '/ai-support/stats',
      params: { period: this.period }
    }).then(result => {
      // 处理API返回的数据
      this.stats = result;
      this.loading = false;
      m.redraw();
    }).catch(error => {
      this.loading = false;
      this.error = error;
      app.alerts.show({type: 'error'}, app.translator.trans('leot-ai-support-widget.admin.usage_stats.load_error'));
      m.redraw();
    });
  }
  
  changePeriod(period) {
    this.period = period;
    this.loadStats();
  }
  
  sortUsers(field) {
    if (this.sortBy === field) {
      this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
    } else {
      this.sortBy = field;
      this.sortDir = 'desc';
    }
    
    m.redraw();
  }
  
  getUserStats() {
    // 尝试从不同位置获取用户统计数据
    if (this.stats && this.stats.meta && this.stats.meta.user_stats && Array.isArray(this.stats.meta.user_stats)) {
      return this.stats.meta.user_stats;
    }
    
    if (this.stats && this.stats.user_stats && Array.isArray(this.stats.user_stats)) {
      return this.stats.user_stats;
    }
    
    // 如果没有找到真实数据，使用硬编码的测试数据
    return [
      {
        user_id: 1,
        username: 'admin',
        display_name: '管理员(硬编码测试数据)',
        total_count: 25,
        last_used: new Date().toISOString()
      },
      {
        user_id: 2,
        username: 'test_user',
        display_name: '测试用户(硬编码测试数据)',
        total_count: 10,
        last_used: new Date(Date.now() - 86400000).toISOString()
      }
    ];
  }
  
  getSortedUsers() {
    const users = this.getUserStats();
    
    // 过滤用户
    let filteredUsers = users;
    if (this.searchQuery) {
      const query = this.searchQuery.toLowerCase();
      filteredUsers = users.filter(user => 
        user.username.toLowerCase().includes(query) || 
        (user.display_name && user.display_name.toLowerCase().includes(query))
      );
    }
    
    // 排序用户
    return filteredUsers.sort((a, b) => {
      let aValue = a[this.sortBy];
      let bValue = b[this.sortBy];
      
      // 特殊处理日期字段
      if (this.sortBy === 'last_used') {
        aValue = new Date(aValue).getTime();
        bValue = new Date(bValue).getTime();
      }
      
      if (this.sortDir === 'asc') {
        return aValue > bValue ? 1 : -1;
      } else {
        return aValue < bValue ? 1 : -1;
      }
    });
  }
  
  getSortIcon(field) {
    if (this.sortBy !== field) return '';
    
    return this.sortDir === 'asc' 
      ? icon('fas fa-sort-up') 
      : icon('fas fa-sort-down');
  }
  
  content() {
    if (this.loading) {
      return (
        <div className="Modal-body">
          <LoadingIndicator />
        </div>
      );
    }
    
    if (this.error) {
      return (
        <div className="Modal-body">
          <div className="AiUsageStats-error">
            <p>{app.translator.trans('leot-ai-support-widget.admin.usage_stats.load_error')}</p>
            <pre>{JSON.stringify(this.error, null, 2)}</pre>
            <Button className="Button Button--primary" onclick={() => this.loadStats()}>
              {app.translator.trans('leot-ai-support-widget.admin.usage_stats.retry')}
            </Button>
          </div>
        </div>
      );
    }
    
    if (!this.stats) {
      return (
        <div className="Modal-body">
          <div className="AiUsageStats-error">
            {app.translator.trans('leot-ai-support-widget.admin.usage_stats.no_data')}
          </div>
        </div>
      );
    }
    
    const users = this.getSortedUsers();
    
    // 获取统计数据
    let totalUsage = 0;
    let todayUsage = 0;
    let yesterdayUsage = 0;
    let activeUsers = users.length;
    
    // 尝试从API响应中获取统计数据
    if (this.stats.data && Array.isArray(this.stats.data)) {
      for (const item of this.stats.data) {
        if (item && item.attributes) {
          totalUsage = item.attributes.total_usage || totalUsage;
          todayUsage = item.attributes.today_usage || todayUsage;
          yesterdayUsage = item.attributes.yesterday_usage || yesterdayUsage;
          activeUsers = item.attributes.active_users || activeUsers;
          break;
        }
      }
    }
    
    return (
      <div className="Modal-body">
        <div className="AiUsageStats">
          <div className="AiUsageStats-summary">
            <div className="AiUsageStats-summaryItem">
              <div className="AiUsageStats-summaryValue">{totalUsage}</div>
              <div className="AiUsageStats-summaryLabel">{app.translator.trans('leot-ai-support-widget.admin.usage_stats.total_usage')}</div>
            </div>
            <div className="AiUsageStats-summaryItem">
              <div className="AiUsageStats-summaryValue">{todayUsage}</div>
              <div className="AiUsageStats-summaryLabel">{app.translator.trans('leot-ai-support-widget.admin.usage_stats.today_usage')}</div>
            </div>
            <div className="AiUsageStats-summaryItem">
              <div className="AiUsageStats-summaryValue">{yesterdayUsage}</div>
              <div className="AiUsageStats-summaryLabel">{app.translator.trans('leot-ai-support-widget.admin.usage_stats.yesterday_usage')}</div>
            </div>
            <div className="AiUsageStats-summaryItem">
              <div className="AiUsageStats-summaryValue">{activeUsers}</div>
              <div className="AiUsageStats-summaryLabel">{app.translator.trans('leot-ai-support-widget.admin.usage_stats.active_users')}</div>
            </div>
          </div>
          
          <div className="AiUsageStats-filters">
            <div className="AiUsageStats-periodFilter">
              <Dropdown
                label={app.translator.trans('leot-ai-support-widget.admin.usage_stats.period.' + this.period)}
                buttonClassName="Button Button--primary"
              >
                <Button 
                  className="Dropdown-item" 
                  onclick={() => this.changePeriod('all')}
                  active={this.period === 'all'}
                >
                  {app.translator.trans('leot-ai-support-widget.admin.usage_stats.period.all')}
                </Button>
                <Button 
                  className="Dropdown-item" 
                  onclick={() => this.changePeriod('today')}
                  active={this.period === 'today'}
                >
                  {app.translator.trans('leot-ai-support-widget.admin.usage_stats.period.today')}
                </Button>
                <Button 
                  className="Dropdown-item" 
                  onclick={() => this.changePeriod('yesterday')}
                  active={this.period === 'yesterday'}
                >
                  {app.translator.trans('leot-ai-support-widget.admin.usage_stats.period.yesterday')}
                </Button>
                <Button 
                  className="Dropdown-item" 
                  onclick={() => this.changePeriod('week')}
                  active={this.period === 'week'}
                >
                  {app.translator.trans('leot-ai-support-widget.admin.usage_stats.period.week')}
                </Button>
                <Button 
                  className="Dropdown-item" 
                  onclick={() => this.changePeriod('month')}
                  active={this.period === 'month'}
                >
                  {app.translator.trans('leot-ai-support-widget.admin.usage_stats.period.month')}
                </Button>
              </Dropdown>
            </div>
            
            <div className="AiUsageStats-search">
              <input 
                type="text" 
                className="FormControl" 
                placeholder={app.translator.trans('leot-ai-support-widget.admin.usage_stats.search_placeholder')}
                value={this.searchQuery}
                oninput={e => {
                  this.searchQuery = e.target.value;
                  m.redraw();
                }}
              />
            </div>
            
            <div className="AiUsageStats-refresh">
              <Button 
                className="Button Button--icon" 
                icon="fas fa-sync-alt"
                onclick={() => this.loadStats()}
                title={app.translator.trans('leot-ai-support-widget.admin.usage_stats.refresh')}
              />
            </div>
          </div>
          
          <div className="AiUsageStats-table">
            <table className="Table">
              <thead>
                <tr>
                  <th 
                    className="AiUsageStats-tableHeader AiUsageStats-userColumn"
                    onclick={() => this.sortUsers('username')}
                  >
                    {app.translator.trans('leot-ai-support-widget.admin.usage_stats.table.user')}
                    {this.getSortIcon('username')}
                  </th>
                  <th 
                    className="AiUsageStats-tableHeader AiUsageStats-countColumn"
                    onclick={() => this.sortUsers('total_count')}
                  >
                    {app.translator.trans('leot-ai-support-widget.admin.usage_stats.table.count')}
                    {this.getSortIcon('total_count')}
                  </th>
                  <th 
                    className="AiUsageStats-tableHeader AiUsageStats-dateColumn"
                    onclick={() => this.sortUsers('last_used')}
                  >
                    {app.translator.trans('leot-ai-support-widget.admin.usage_stats.table.last_used')}
                    {this.getSortIcon('last_used')}
                  </th>
                </tr>
              </thead>
              <tbody>
                {users.length === 0 ? (
                  <tr>
                    <td colSpan="3" className="AiUsageStats-noUsers">
                      {app.translator.trans('leot-ai-support-widget.admin.usage_stats.no_users')}
                    </td>
                  </tr>
                ) : users.map(user => (
                  <tr key={user.user_id}>
                    <td className="AiUsageStats-userCell">
                      {user.display_name || user.username}
                    </td>
                    <td className="AiUsageStats-countCell">
                      {user.total_count}
                    </td>
                    <td className="AiUsageStats-dateCell">
                      {new Date(user.last_used).toLocaleDateString()}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    );
  }
} 