import 'package:flutter/material.dart';
import '../../api/message_service.dart';

class ChatDetailScreen extends StatefulWidget {
  final Map<String, dynamic> consultation;
  const ChatDetailScreen({Key? key, required this.consultation}) : super(key: key);

  @override
  State<ChatDetailScreen> createState() => _ChatDetailScreenState();
}

class _ChatDetailScreenState extends State<ChatDetailScreen> {
  List<dynamic> messages = [];
  bool isLoading = true;
  String? errorMessage;

  @override
  void initState() {
    super.initState();
    _loadMessages();
  }

  Future<void> _loadMessages() async {
    setState(() {
      isLoading = true;
      errorMessage = null;
    });
    try {
      final result = await MessageService.getMessagesByConsultation(widget.consultation['_id']);
      if (result['success'] != true) {
        setState(() {
          errorMessage = result['message'] ?? 'Không thể lấy tin nhắn';
          isLoading = false;
        });
        return;
      }
      final rows = result['data']['rows'] as List<dynamic>?;
      final filtered = rows?.map((row) => row['doc']).where((msg) => msg['consultation_id'] == widget.consultation['_id']).toList() ?? [];
      setState(() {
        messages = filtered;
        isLoading = false;
      });
    } catch (e) {
      setState(() {
        errorMessage = 'Lỗi kết nối: $e';
        isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final staffInfo = widget.consultation['staff_info'] ?? {};
    final staffName = staffInfo['name'] ?? '';
    final status = widget.consultation['status'] ?? '';

    Widget messageListWidget;
    if (isLoading) {
      messageListWidget = const Center(child: CircularProgressIndicator());
    } else if (errorMessage != null) {
      messageListWidget = Center(child: Text(errorMessage!));
    } else if (messages.isEmpty) {
      messageListWidget = const Center(child: Text('Chưa có tin nhắn nào'));
    } else {
      messageListWidget = ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: messages.length,
        itemBuilder: (context, index) {
          final msg = messages[index];
          return MessageBubble(message: msg);
        },
      );
    }

    Widget? bottomWidget;
    if (status == 'closed') {
      bottomWidget = Container(
        width: double.infinity,
        color: Colors.grey.shade100,
        padding: const EdgeInsets.all(16),
        child: const Text(
          'Cuộc hội thoại đã kết thúc, vui lòng tạo một cuộc hội thoại mới để được tư vấn.',
          style: TextStyle(color: Colors.red, fontWeight: FontWeight.bold),
          textAlign: TextAlign.center,
        ),
      );
    } else if (status == 'active' || status == 'waiting') {
      bottomWidget = MessageInput(
        consultation: widget.consultation,
        onSent: _loadMessages,
      );
    }

    return Scaffold(
      appBar: AppBar(
        title: Text('Chat với $staffName'),
        backgroundColor: Colors.blue.shade600,
      ),
      body: Column(
        children: [
          Expanded(child: messageListWidget),
          if (bottomWidget != null) bottomWidget,
        ],
      ),
    );
  }
}

class MessageInput extends StatefulWidget {
  final Map<String, dynamic> consultation;
  final VoidCallback onSent;
  const MessageInput({Key? key, required this.consultation, required this.onSent}) : super(key: key);

  @override
  State<MessageInput> createState() => _MessageInputState();
}

class _MessageInputState extends State<MessageInput> {
  final TextEditingController _controller = TextEditingController();
  bool _sending = false;

  Future<void> _send() async {
    final text = _controller.text.trim();
    if (text.isEmpty) return;
    setState(() { _sending = true; });
    try {
      final patientInfo = widget.consultation['patient_info'] ?? {};
      final senderId = widget.consultation['patient_id'] ?? '';
      final senderName = patientInfo['name'] ?? '';
      final consultationId = widget.consultation['_id'] ?? '';
      // Generate _id: message_giâyphútgiờngàythángnăm (ssmmHHddMMyyyy)
      final now = DateTime.now();
      final messageId = 'message_' +
        now.second.toString().padLeft(2, '0') +
        now.minute.toString().padLeft(2, '0') +
        now.hour.toString().padLeft(2, '0') +
        now.day.toString().padLeft(2, '0') +
        now.month.toString().padLeft(2, '0') +
        now.year.toString().padLeft(4, '0');
      final messageData = {
        '_id': messageId,
        'consultation_id': consultationId,
        'sender_id': senderId,
        'sender_type': 'patient',
        'sender_name': senderName,
        'message': text,
      };
      print('DEBUG: messageData gửi lên:');
      print(messageData);
      final result = await MessageService.sendMessage(messageData);
      print('DEBUG: Kết quả trả về từ API:');
      print(result);
      if (result['success'] == true) {
        _controller.clear();
        widget.onSent();
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(result['message'] ?? 'Không thể gửi tin nhắn'), backgroundColor: Colors.red),
        );
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Lỗi gửi tin nhắn: $e'), backgroundColor: Colors.red),
      );
    } finally {
      setState(() { _sending = false; });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      color: Colors.white,
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      child: Row(
        children: [
          Expanded(
            child: TextField(
              controller: _controller,
              enabled: !_sending,
              decoration: const InputDecoration(
                hintText: 'Nhập tin nhắn...',
                border: OutlineInputBorder(),
                isDense: true,
                contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 10),
              ),
              onSubmitted: (_) => _send(),
            ),
          ),
          const SizedBox(width: 8),
          _sending
              ? const SizedBox(width: 32, height: 32, child: CircularProgressIndicator(strokeWidth: 2))
              : IconButton(
                  icon: const Icon(Icons.send, color: Colors.blue),
                  onPressed: _send,
                ),
        ],
      ),
    );
  }
}

class MessageBubble extends StatelessWidget {
  final Map<String, dynamic> message;
  const MessageBubble({Key? key, required this.message}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    final isPatient = message['sender_type'] == 'patient';
    final senderName = message['sender_name'] ?? '';
    final text = message['message'] ?? '';
    final createdAt = message['created_at'] ?? '';
    return Align(
      alignment: isPatient ? Alignment.centerRight : Alignment.centerLeft,
      child: Container(
        margin: const EdgeInsets.symmetric(vertical: 6),
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: isPatient ? Colors.blue.shade100 : Colors.grey.shade200,
          borderRadius: BorderRadius.circular(12),
        ),
        child: Column(
          crossAxisAlignment:
              isPatient ? CrossAxisAlignment.end : CrossAxisAlignment.start,
          children: [
            Text(senderName, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
            const SizedBox(height: 4),
            Text(text),
            const SizedBox(height: 4),
            Text(createdAt, style: const TextStyle(fontSize: 11, color: Colors.grey)),
          ],
        ),
      ),
    );
  }
}
