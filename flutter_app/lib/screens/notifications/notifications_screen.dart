import 'package:flutter/material.dart';
import 'package:dio/dio.dart';
import '../../core/api/api_client.dart';
import '../../core/theme/app_theme.dart';

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({super.key});

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  final Dio _dio = ApiClient.create();
  List<Map<String, dynamic>> _notifications = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _loadNotifications();
  }

  Future<void> _loadNotifications() async {
    setState(() => _loading = true);
    try {
      final response = await _dio.get('/notifications');
      final list = response.data['data'] as List;
      setState(() {
        _notifications = list.map((n) => n as Map<String, dynamic>).toList();
        _loading = false;
      });
    } catch (_) {
      setState(() => _loading = false);
    }
  }

  Future<void> _markAllRead() async {
    try {
      await _dio.put('/notifications/mark-all-read');
      _loadNotifications();
    } catch (_) {}
  }

  IconData _iconForType(String type) {
    return switch (type) {
      'booking_confirmed' => Icons.check_circle,
      'booking_reminder'  => Icons.alarm,
      'booking_update'    => Icons.update,
      _ => Icons.notifications,
    };
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Notifications'),
        actions: [
          if (_notifications.any((n) => n['read_at'] == null))
            TextButton(
              onPressed: _markAllRead,
              child: const Text('Mark all read', style: TextStyle(color: Colors.white)),
            ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _notifications.isEmpty
              ? const Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.notifications_none, size: 64, color: AppTheme.textSecondary),
                      SizedBox(height: 16),
                      Text('No notifications', style: TextStyle(color: AppTheme.textSecondary)),
                    ],
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _loadNotifications,
                  child: ListView.separated(
                    itemCount: _notifications.length,
                    separatorBuilder: (_, __) => const Divider(height: 1),
                    itemBuilder: (_, i) {
                      final n = _notifications[i];
                      final isRead = n['read_at'] != null;
                      return ListTile(
                        tileColor: isRead ? null : AppTheme.primaryLight,
                        leading: CircleAvatar(
                          backgroundColor: isRead ? Colors.grey.shade200 : AppTheme.primary,
                          child: Icon(_iconForType(n['type'] ?? ''), color: isRead ? Colors.grey : Colors.white, size: 20),
                        ),
                        title: Text(n['title'] ?? '', style: TextStyle(fontWeight: isRead ? FontWeight.normal : FontWeight.bold)),
                        subtitle: Text(n['body'] ?? ''),
                        trailing: !isRead
                            ? Container(width: 8, height: 8, decoration: const BoxDecoration(color: AppTheme.primary, shape: BoxShape.circle))
                            : null,
                        onTap: () async {
                          if (!isRead) {
                            await _dio.put('/notifications/${n['id']}/read');
                            _loadNotifications();
                          }
                        },
                      );
                    },
                  ),
                ),
    );
  }
}
