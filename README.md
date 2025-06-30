# Flarum AI Support Widget / Flarum AI 支持小部件

[English](#english) | [中文](#chinese)

<a name="english"></a>
## Introduction

Flarum AI Support Widget is an artificial intelligence assistant plugin designed for Flarum forums, helping users quickly get answers to questions related to forum content. The plugin integrates AI services like OpenAI or DeepSeek, combined with forum content search functionality, to provide accurate answers.

## Features

- **Multiple AI Service Support**: Supports various AI services including OpenAI and DeepSeek
- **Intelligent Search**: Automatically searches related content in the forum as references for AI answers
- **Multilingual Support**: Automatically detects the user's language and replies in the same language
- **Permission Control**: Administrators can set which user groups can use the AI assistant
- **Usage Limits**: Configurable daily usage limits to prevent resource abuse
- **Knowledge Base Integration**: Integrates with knowledge base content for more accurate answers
- **Conversation History**: Supports multi-turn conversations with context memory
- **Domain Specialization**: Specially optimized for car navigation systems and Android head units

## Requirements

- Flarum >= 1.0.0
- PHP >= 7.4
- Valid OpenAI API key or DeepSeek API key

## Installation

```bash
composer require leot/flarum-ai-support-widget
```

## Configuration

1. Enable the plugin in the Flarum admin panel
2. Go to the plugin settings page
3. Select an AI service provider (OpenAI or DeepSeek)
4. Enter your API key
5. Configure model name, daily request limits, and other parameters
6. Set user permissions

## Usage

After installation, users with permission will see the AI assistant widget in the forum interface. Users can ask questions directly in the widget, and the AI will provide answers based on forum content and its knowledge.

## Custom Settings

- **Daily Request Limit**: Control how many times each user can use the AI assistant per day
- **Search Result Count**: Set the number of forum posts the AI references
- **Response Length**: Set the maximum length of AI responses
- **Timeout Settings**: Set the API request timeout

## FAQ

1. **Why is the AI assistant not visible?**
   - Check if the user has permission to use it
   - Confirm the plugin is properly enabled

2. **Why are API requests failing?**
   - Check if the API key is correct
   - Ensure network connectivity is normal
   - Check PHP error logs for detailed information

3. **How to improve answer quality?**
   - Increase the number of search results
   - Use more advanced AI models
   - Ensure forum content is rich and relevant

## License

MIT License

## Author

LeoT

## Version History

- 1.0.2: Enhanced system prompts and improved AI response quality
- 1.0.0: Initial release

---


## 简介

Flarum AI 支持小部件是一个为 Flarum 论坛设计的人工智能助手插件，可以帮助用户快速获取论坛内容相关的问题解答。该插件通过集成 OpenAI 或 DeepSeek 等 AI 服务，结合论坛内容搜索功能，为用户提供精准的回答。

## 功能特点

- **多 AI 服务支持**：支持 OpenAI 和 DeepSeek 等多种 AI 服务
- **智能搜索**：自动搜索论坛内相关内容，作为 AI 回答的参考
- **多语言支持**：自动识别用户使用的语言，并以相同语言回复
- **权限控制**：管理员可以设置哪些用户组可以使用 AI 助手
- **使用限制**：可设置每日使用次数限制，避免资源滥用
- **知识库集成**：支持与知识库内容结合，提供更精准的回答
- **会话历史**：支持多轮对话，AI 能记住上下文
- **领域特化**：针对车载导航系统和安卓主机领域进行了特别优化

## 安装要求

- Flarum >= 1.0.0
- PHP >= 7.4
- 有效的 OpenAI API 密钥或 DeepSeek API 密钥

## 安装方法

```bash
composer require leot/flarum-ai-support-widget
```

## 配置说明

1. 在 Flarum 管理面板中启用插件
2. 进入插件设置页面
3. 选择 AI 服务提供商（OpenAI 或 DeepSeek）
4. 输入 API 密钥
5. 配置模型名称、每日请求限制等参数
6. 设置用户权限

## 使用方法

插件安装后，具有权限的用户将在论坛界面看到 AI 助手小部件。用户可以直接在小部件中提问，AI 会结合论坛内容和自身知识给出回答。

## 自定义设置

- **每日请求限制**：控制每个用户每天可以使用 AI 助手的次数
- **搜索结果数量**：设置 AI 参考的论坛帖子数量
- **响应长度**：设置 AI 回答的最大长度
- **超时设置**：设置 API 请求超时时间

## 常见问题

1. **为什么 AI 助手不可见？**
   - 检查用户是否有使用权限
   - 确认插件已正确启用

2. **为什么 API 请求失败？**
   - 检查 API 密钥是否正确
   - 确认网络连接正常
   - 查看 PHP 错误日志获取详细信息

3. **如何提高回答质量？**
   - 增加搜索结果数量
   - 使用更高级的 AI 模型
   - 确保论坛内容丰富且相关

## 许可证

MIT License

## 作者

LeoT

## 版本历史

- 1.0.2：增强系统提示词，提升AI回复质量
- 1.0.0：初始版本发布
