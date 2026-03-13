import 'package:flutter/material.dart';

import 'clearance_history_item.dart';
import 'history_card.dart';
import 'history_service.dart';

class HistoryScreen extends StatefulWidget {
  const HistoryScreen({super.key});

  @override
  State<HistoryScreen> createState() => _HistoryScreenState();
}

class _HistoryScreenState extends State<HistoryScreen> {
  final HistoryService _historyService = HistoryService();

  List<ClearanceHistoryItem> _items = [];
  int _currentPage = 1;
  int _lastPage = 1;
  bool _isLoading = false;
  bool _isLoadingMore = false;
  String? _errorMessage;

  static const Color _lnuNavy = Color(0xFF1B3A6B);

  @override
  void initState() {
    super.initState();
    _fetchHistory(page: 1);
  }

  Future<void> _fetchHistory({int page = 1}) async {
    setState(() {
      _errorMessage = null;
      if (page > 1) {
        _isLoadingMore = true;
      } else {
        _isLoading = true;
      }
    });

    try {
      final response = await _historyService.fetchHistory(page: page);
      if (!mounted) return;

      final data = response['data'];
      final meta = response['meta'];
      final items = data is List
          ? data
              .whereType<Map<String, dynamic>>()
              .map(ClearanceHistoryItem.fromJson)
              .toList()
          : <ClearanceHistoryItem>[];

      final currentPage = meta is Map<String, dynamic>
          ? (meta['current_page'] as num?)?.toInt()
          : null;
      final lastPage = meta is Map<String, dynamic>
          ? (meta['last_page'] as num?)?.toInt()
          : null;

      setState(() {
        _currentPage = currentPage ?? page;
        _lastPage = lastPage ?? _lastPage;
        if (page > 1) {
          _items = [..._items, ...items];
        } else {
          _items = items;
        }
      });
    } catch (e) {
      if (!mounted) return;

      setState(() {
        _errorMessage = e is Exception
            ? e.toString().replaceFirst('Exception: ', '')
            : 'Unable to load clearance history.';
      });
    } finally {
      if (!mounted) return;
      setState(() {
        _isLoading = false;
        _isLoadingMore = false;
      });
    }
  }

  Future<void> _handleRefresh() async {
    setState(() {
      _items = [];
      _currentPage = 1;
      _lastPage = 1;
    });
    await _fetchHistory(page: 1);
  }

  void _openDetail(ClearanceHistoryItem item) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => ClearanceDetailScreen(requestId: item.id),
      ),
    );
  }

  void _returnToDashboard() {
    if (Navigator.of(context).canPop()) {
      Navigator.of(context).pop();
    }
  }

  bool _handleScroll(ScrollNotification scrollInfo) {
    final shouldLoadMore =
        scrollInfo.metrics.pixels >=
            scrollInfo.metrics.maxScrollExtent - 200 &&
            _currentPage < _lastPage &&
            !_isLoadingMore;

    if (shouldLoadMore) {
      _fetchHistory(page: _currentPage + 1);
    }

    return false;
  }

  Widget _buildErrorState() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 24),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(
              Icons.error_outline,
              color: Colors.red,
              size: 48,
            ),
            const SizedBox(height: 12),
            Text(
              _errorMessage ?? 'Something went wrong.',
              textAlign: TextAlign.center,
              style: const TextStyle(fontSize: 15, color: Colors.black87),
            ),
            const SizedBox(height: 16),
            OutlinedButton(
              onPressed: () => _fetchHistory(page: 1),
              style: OutlinedButton.styleFrom(
                foregroundColor: _lnuNavy,
              ),
              child: const Text('Try Again'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildEmptyState() {
    return RefreshIndicator(
      onRefresh: _handleRefresh,
      child: LayoutBuilder(
        builder: (context, constraints) => SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          child: ConstrainedBox(
            constraints: BoxConstraints(minHeight: constraints.maxHeight),
            child: Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Icon(
                    Icons.history,
                    size: 64,
                    color: Colors.grey,
                  ),
                  const SizedBox(height: 12),
                  const Text(
                    'No clearance history yet.',
                    style: TextStyle(fontSize: 16, color: Colors.grey),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    'Your past and current requests will appear here.',
                    style: TextStyle(color: Colors.grey.shade600),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isInitialLoading = _isLoading && _items.isEmpty;
    final hasError = _errorMessage != null && _items.isEmpty;

    return Scaffold(
      appBar: AppBar(
        backgroundColor: _lnuNavy,
        title: const Text(
          'Clearance History',
          style: TextStyle(color: Colors.white),
        ),
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: isInitialLoading
          ? const Center(
              child: CircularProgressIndicator(color: _lnuNavy),
            )
          : hasError
              ? _buildErrorState()
              : _items.isEmpty
                  ? _buildEmptyState()
                  : NotificationListener<ScrollNotification>(
                      onNotification: _handleScroll,
                      child: RefreshIndicator(
                        onRefresh: _handleRefresh,
                        child: ListView.builder(
                          physics: const AlwaysScrollableScrollPhysics(),
                          padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
                          itemCount:
                              _items.length + (_isLoadingMore ? 1 : 0),
                          itemBuilder: (context, index) {
                            if (index >= _items.length) {
                              return const Padding(
                                padding: EdgeInsets.symmetric(vertical: 12),
                                child: Center(
                                  child: SizedBox(
                                    height: 20,
                                    width: 20,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2,
                                      color: _lnuNavy,
                                    ),
                                  ),
                                ),
                              );
                            }

                            final item = _items[index];
                            final status = item.status.toLowerCase();

                            VoidCallback? onTap;
                            if (status == 'completed') {
                              onTap = () => _openDetail(item);
                            } else if (status == 'pending') {
                              onTap = _returnToDashboard;
                            }

                            return HistoryCard(item: item, onTap: onTap);
                          },
                        ),
                      ),
                    ),
    );
  }
}

class ClearanceDetailScreen extends StatelessWidget {
  const ClearanceDetailScreen({super.key, required this.requestId});

  final int requestId;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Clearance Detail'),
      ),
      body: const Center(
        child: Text('Coming in LDCS-53'),
      ),
    );
  }
}
