import 'dart:async';

import 'package:flutter/material.dart';

import '../auth/auth_repository.dart';
import '../theme/app_theme.dart';
import 'company_chat_repository.dart';

class CompanyChatScreen extends StatefulWidget {
  const CompanyChatScreen({
    required this.session,
    required this.active,
    required this.onUnreadChanged,
    required this.requestedCompanyId,
    required this.openRequestSerial,
    super.key,
  });

  final DriverSession session;
  final bool active;
  final ValueChanged<int> onUnreadChanged;
  final int? requestedCompanyId;
  final int openRequestSerial;

  @override
  State<CompanyChatScreen> createState() => _CompanyChatScreenState();
}

class _CompanyChatScreenState extends State<CompanyChatScreen> {
  final _repository = CompanyChatRepository();
  final _messageController = TextEditingController();
  final _scrollController = ScrollController();
  List<ChatConversation> _conversations = const [];
  List<CompanyChatMessage> _messages = const [];
  ChatConversation? _selectedConversation;
  Timer? _pollTimer;
  bool _loading = true;
  bool _refreshing = false;
  bool _sending = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadConversations();
    _syncPolling();
  }

  @override
  void didUpdateWidget(covariant CompanyChatScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.active != widget.active) {
      _syncPolling();
      if (widget.active) _refresh(silent: true);
    }
    if (oldWidget.openRequestSerial != widget.openRequestSerial &&
        widget.requestedCompanyId != null) {
      _openRequestedCompany(widget.requestedCompanyId!);
    }
  }

  @override
  void dispose() {
    _pollTimer?.cancel();
    _messageController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  void _syncPolling() {
    _pollTimer?.cancel();
    if (!widget.active) return;
    _pollTimer = Timer.periodic(
      const Duration(seconds: 2),
      (_) => _refresh(silent: true),
    );
  }

  Future<void> _refresh({bool silent = false}) => _selectedConversation == null
      ? _loadConversations(silent: silent)
      : _loadMessages(silent: silent);

  Future<void> _loadConversations({bool silent = false}) async {
    if (_refreshing) return;
    _refreshing = true;
    if (!silent && mounted) {
      setState(() {
        _loading = true;
        _error = null;
      });
    }
    try {
      final conversations = await _repository.getConversations(
        widget.session.token,
      );
      if (!mounted) return;
      final selectedId = _selectedConversation?.id;
      setState(() {
        _conversations = conversations;
        if (selectedId != null) {
          _selectedConversation = conversations
              .where((item) => item.id == selectedId)
              .firstOrNull;
        }
        _loading = false;
        _error = null;
      });
      widget.onUnreadChanged(
        conversations.fold(0, (total, item) => total + item.unreadCount),
      );
      if (widget.requestedCompanyId != null) {
        _refreshing = false;
        await _openRequestedCompany(widget.requestedCompanyId!);
      }
    } on CompanyChatException catch (error) {
      if (mounted && !silent) {
        setState(() {
          _loading = false;
          _error = error.message;
        });
      }
    } finally {
      _refreshing = false;
    }
  }

  Future<void> _openRequestedCompany(int companyId) async {
    if (_conversations.isEmpty) return;
    final conversation = _conversations
        .where((item) => item.isCompany && item.participantId == companyId)
        .firstOrNull;
    if (conversation == null || _selectedConversation?.id == conversation.id) {
      return;
    }
    await _openConversation(conversation);
  }

  Future<void> _openConversation(ChatConversation conversation) async {
    setState(() {
      _selectedConversation = conversation;
      _messages = const [];
      _loading = true;
      _error = null;
    });
    await _loadMessages();
  }

  Future<void> _loadMessages({bool silent = false}) async {
    final conversation = _selectedConversation;
    if (conversation == null || _refreshing) return;
    var refreshConversations = false;
    _refreshing = true;
    if (!silent && mounted) {
      setState(() {
        _loading = true;
        _error = null;
      });
    }
    try {
      final messages = await _repository.getMessages(
        widget.session.token,
        companyId: conversation.isCompany ? conversation.participantId : null,
      );
      if (!mounted) return;
      final previousLastId = _messages.isEmpty ? null : _messages.last.id;
      final nextLastId = messages.isEmpty ? null : messages.last.id;
      setState(() {
        _messages = messages;
        _loading = false;
        _error = null;
      });

      final unread = messages
          .where((message) => !message.isDriver && !message.read)
          .toList();
      if (unread.isNotEmpty) {
        await Future.wait(
          unread.map(
            (message) =>
                _repository.markAsRead(widget.session.token, message.id),
          ),
        );
        refreshConversations = true;
      }
      if (!silent || previousLastId != nextLastId) {
        WidgetsBinding.instance.addPostFrameCallback((_) => _scrollToEnd());
      }
    } on CompanyChatException catch (error) {
      if (mounted && !silent) {
        setState(() {
          _loading = false;
          _error = error.message;
        });
      }
    } finally {
      _refreshing = false;
    }
    if (refreshConversations) {
      await _loadConversations(silent: true);
    }
  }

  void _closeConversation() {
    setState(() {
      _selectedConversation = null;
      _messages = const [];
      _loading = false;
      _error = null;
    });
    _loadConversations(silent: true);
  }

  void _scrollToEnd() {
    if (!_scrollController.hasClients) return;
    _scrollController.animateTo(
      _scrollController.position.maxScrollExtent,
      duration: const Duration(milliseconds: 250),
      curve: Curves.easeOut,
    );
  }

  Future<void> _send() async {
    final text = _messageController.text.trim();
    final companyMessages = _messages.where((message) => !message.isDriver);
    if (text.isEmpty || _sending || companyMessages.isEmpty) return;
    setState(() => _sending = true);
    try {
      await _repository.sendReply(
        widget.session.token,
        companyMessages.last.id,
        text,
      );
      _messageController.clear();
      await _loadMessages(silent: true);
    } on CompanyChatException catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text(error.message)));
      }
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return _selectedConversation == null
        ? _buildConversationList()
        : _buildThread(_selectedConversation!);
  }

  Widget _buildConversationList() {
    return Column(
      children: [
        Container(
          width: double.infinity,
          padding: const EdgeInsets.fromLTRB(18, 14, 18, 14),
          color: const Color(0xFFEAF4FF),
          child: Row(
            children: [
              const CircleAvatar(
                backgroundColor: AppTheme.blue,
                foregroundColor: Colors.white,
                child: Icon(Icons.forum_rounded),
              ),
              const SizedBox(width: 11),
              const Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'گفتگوهای من',
                      style: TextStyle(fontWeight: FontWeight.w900),
                    ),
                    Text(
                      'هر مخاطب در یک گفتگوی مستقل',
                      style: TextStyle(color: Color(0xFF64748B), fontSize: 11),
                    ),
                  ],
                ),
              ),
              IconButton(
                onPressed: _loading ? null : _loadConversations,
                tooltip: 'به‌روزرسانی',
                icon: const Icon(Icons.refresh_rounded),
              ),
            ],
          ),
        ),
        Expanded(child: _buildConversationListBody()),
      ],
    );
  }

  Widget _buildConversationListBody() {
    if (_loading) return const Center(child: CircularProgressIndicator());
    if (_error != null) return _errorView(_loadConversations);
    if (_conversations.isEmpty) {
      return const Center(
        child: Padding(
          padding: EdgeInsets.all(32),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(
                Icons.mark_chat_unread_outlined,
                size: 62,
                color: Color(0xFF94A3B8),
              ),
              SizedBox(height: 14),
              Text('هنوز گفتگویی برای شما ایجاد نشده است.'),
            ],
          ),
        ),
      );
    }
    return RefreshIndicator(
      onRefresh: _loadConversations,
      child: ListView.separated(
        padding: const EdgeInsets.all(14),
        itemCount: _conversations.length,
        separatorBuilder: (_, _) => const SizedBox(height: 9),
        itemBuilder: (context, index) {
          final conversation = _conversations[index];
          return Material(
            color: Colors.white,
            borderRadius: BorderRadius.circular(18),
            child: InkWell(
              borderRadius: BorderRadius.circular(18),
              onTap: () => _openConversation(conversation),
              child: Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(18),
                  border: Border.all(
                    color: conversation.unreadCount > 0
                        ? const Color(0xFF60A5FA)
                        : const Color(0xFFE2E8F0),
                  ),
                ),
                child: Row(
                  children: [
                    CircleAvatar(
                      backgroundColor: conversation.isCompany
                          ? const Color(0xFFEAF4FF)
                          : const Color(0xFFE8FFF6),
                      foregroundColor: conversation.isCompany
                          ? AppTheme.blue
                          : AppTheme.green,
                      child: Icon(
                        conversation.isCompany
                            ? Icons.apartment_rounded
                            : Icons.person_rounded,
                      ),
                    ),
                    const SizedBox(width: 11),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Expanded(
                                child: Text(
                                  conversation.title,
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: const TextStyle(
                                    fontWeight: FontWeight.w900,
                                  ),
                                ),
                              ),
                              Text(
                                conversation.lastMessageAt,
                                style: const TextStyle(
                                  fontSize: 8,
                                  color: Color(0xFF94A3B8),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 4),
                          Text(
                            conversation.lastMessage,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: TextStyle(
                              fontSize: 11,
                              color: conversation.unreadCount > 0
                                  ? const Color(0xFF0F172A)
                                  : const Color(0xFF64748B),
                              fontWeight: conversation.unreadCount > 0
                                  ? FontWeight.w800
                                  : FontWeight.normal,
                            ),
                          ),
                        ],
                      ),
                    ),
                    if (conversation.unreadCount > 0) ...[
                      const SizedBox(width: 8),
                      Badge.count(count: conversation.unreadCount),
                    ],
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildThread(ChatConversation conversation) {
    final canReply =
        conversation.canReply && _messages.any((message) => !message.isDriver);
    return Column(
      children: [
        Container(
          width: double.infinity,
          padding: const EdgeInsets.fromLTRB(8, 10, 12, 10),
          color: const Color(0xFFEAF4FF),
          child: Row(
            children: [
              IconButton(
                onPressed: _closeConversation,
                icon: const Icon(Icons.arrow_back_rounded),
                tooltip: 'بازگشت به گفتگوها',
              ),
              CircleAvatar(
                backgroundColor: AppTheme.blue,
                foregroundColor: Colors.white,
                child: Icon(
                  conversation.isCompany
                      ? Icons.apartment_rounded
                      : Icons.person_rounded,
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      conversation.title,
                      style: const TextStyle(fontWeight: FontWeight.w900),
                    ),
                    Text(
                      conversation.subtitle,
                      style: const TextStyle(
                        color: Color(0xFF64748B),
                        fontSize: 10,
                      ),
                    ),
                  ],
                ),
              ),
              IconButton(
                onPressed: _loading ? null : _loadMessages,
                icon: const Icon(Icons.refresh_rounded),
              ),
            ],
          ),
        ),
        Expanded(child: _buildMessages()),
        SafeArea(
          top: false,
          child: Container(
            padding: const EdgeInsets.fromLTRB(12, 10, 12, 12),
            decoration: const BoxDecoration(
              color: Colors.white,
              border: Border(top: BorderSide(color: Color(0xFFE2E8F0))),
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Expanded(
                  child: TextField(
                    controller: _messageController,
                    enabled: canReply && !_sending,
                    minLines: 1,
                    maxLines: 4,
                    maxLength: 1000,
                    decoration: InputDecoration(
                      counterText: '',
                      hintText: canReply
                          ? 'پیام خود را بنویسید...'
                          : 'امکان پاسخ برای این گفتگو فعال نیست',
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                IconButton.filled(
                  onPressed: canReply && !_sending ? _send : null,
                  icon: _sending
                      ? const SizedBox(
                          width: 19,
                          height: 19,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: Colors.white,
                          ),
                        )
                      : const Icon(Icons.send_rounded),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildMessages() {
    if (_loading) return const Center(child: CircularProgressIndicator());
    if (_error != null) return _errorView(_loadMessages);
    if (_messages.isEmpty) {
      return const Center(child: Text('هنوز پیامی در این گفتگو ثبت نشده است.'));
    }
    return ListView.builder(
      controller: _scrollController,
      padding: const EdgeInsets.all(14),
      itemCount: _messages.length,
      itemBuilder: (context, index) {
        final message = _messages[index];
        return Align(
          alignment: message.isDriver
              ? Alignment.centerLeft
              : Alignment.centerRight,
          child: Container(
            constraints: const BoxConstraints(maxWidth: 310),
            margin: const EdgeInsets.only(bottom: 10),
            padding: const EdgeInsets.fromLTRB(14, 11, 14, 9),
            decoration: BoxDecoration(
              color: message.isDriver ? AppTheme.blue : Colors.white,
              borderRadius: BorderRadius.circular(17).copyWith(
                bottomLeft: message.isDriver ? const Radius.circular(4) : null,
                bottomRight: message.isDriver ? null : const Radius.circular(4),
              ),
              border: message.isDriver
                  ? null
                  : Border.all(color: const Color(0xFFE2E8F0)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  message.message,
                  style: TextStyle(
                    color: message.isDriver
                        ? Colors.white
                        : const Color(0xFF0F172A),
                    height: 1.6,
                  ),
                ),
                const SizedBox(height: 5),
                Text(
                  message.createdAt,
                  textDirection: TextDirection.ltr,
                  style: TextStyle(
                    color: message.isDriver
                        ? const Color(0xFFDBEAFE)
                        : const Color(0xFF94A3B8),
                    fontSize: 9,
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _errorView(Future<void> Function({bool silent}) retry) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(28),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.cloud_off_rounded, size: 48),
            const SizedBox(height: 12),
            Text(_error!, textAlign: TextAlign.center),
            const SizedBox(height: 12),
            FilledButton(
              onPressed: () => retry(),
              child: const Text('تلاش مجدد'),
            ),
          ],
        ),
      ),
    );
  }
}
