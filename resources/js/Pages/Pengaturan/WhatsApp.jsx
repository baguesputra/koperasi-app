import React, { useState, useEffect, useRef } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { Check, X, Loader2, MessageSquare, QrCode, Wifi, WifiOff, RefreshCw, Trash2, Edit, Plus, Send, Eye, AlertCircle, ChevronDown, ChevronUp } from 'lucide-react';

export default function WhatsAppSettings() {
    const { auth, flash } = usePage().props;
    const { sessions, logs, filters, statusOptions } = usePage().props;

    const [activeTab, setActiveTab] = useState('sessions');
    const [showCreateModal, setShowCreateModal] = useState(false);
    const [editingSession, setEditingSession] = useState(null);
    const [testSend, setTestSend] = useState({ session_id: '', to: '', message: '' });
    const [showTestModal, setShowTestModal] = useState(false);
    const [autoRefresh, setAutoRefresh] = useState(true);
    const [showQrModal, setShowQrModal] = useState(false);
    const [qrCodeData, setQrCodeData] = useState(null);
    const [qrLoading, setQrLoading] = useState(false);
    const [qrSessionId, setQrSessionId] = useState(null);
    const qrRefreshInterval = useRef(null);

    const createForm = useForm({
        session_id: '',
        name: '',
        description: '',
        is_default: false,
    });

    const editForm = useForm({
        name: '',
        description: '',
        is_default: false,
        is_active: true,
    });

    const logFilters = useForm({
        session_id: '',
        status: '',
        date_from: '',
        date_to: '',
        search: '',
    });

    useEffect(() => {
        if (activeTab === 'sessions' && autoRefresh) {
            qrRefreshInterval.current = setInterval(() => {
                if (sessions.some(s => !s.phone_number && s.is_active)) {
                    window.location.reload();
                }
            }, 10000);
        }
        return () => clearInterval(qrRefreshInterval.current);
    }, [activeTab, autoRefresh, sessions]);

    useEffect(() => {
        if (editingSession) {
            editForm.setData({
                name: editingSession.name,
                description: editingSession.description || '',
                is_default: editingSession.is_default,
                is_active: editingSession.is_active,
            });
        }
    }, [editingSession]);

    useEffect(() => {
        return () => clearInterval(qrRefreshInterval.current);
    }, []);

    const fetchQrCode = async (sessionId) => {
        setQrLoading(true);
        setQrSessionId(sessionId);
        try {
            const response = await fetch(route('pengaturan.whatsapp.qr', { session_id: sessionId }));
            const data = await response.json();
            if (data.qr) {
                setQrCodeData(data.qr);
                setShowQrModal(true);
                qrRefreshInterval.current = setInterval(async () => {
                    const resp = await fetch(route('pengaturan.whatsapp.qr', { session_id: sessionId }));
                    const d = await resp.json();
                    if (d.qr) {
                        setQrCodeData(d.qr);
                    } else if (d.connected) {
                        clearInterval(qrRefreshInterval.current);
                        setShowQrModal(false);
                        window.location.reload();
                    }
                }, 5000);
            } else if (data.connected) {
                window.location.reload();
            } else {
                alert(data.error || 'QR tidak tersedia');
            }
        } catch (err) {
            alert('Gagal mengambil QR code');
        } finally {
            setQrLoading(false);
        }
    };

    const closeQrModal = () => {
        clearInterval(qrRefreshInterval.current);
        setShowQrModal(false);
        setQrCodeData(null);
        setQrSessionId(null);
    };

    const handleCreate = (e) => {
        e.preventDefault();
        createForm.post(route('pengaturan.whatsapp.session.store'), {
            onSuccess: () => {
                createForm.reset();
                setShowCreateModal(false);
            },
        });
    };

    const handleUpdate = (e) => {
        e.preventDefault();
        editForm.put(route('pengaturan.whatsapp.session.update', editingSession.id), {
            onSuccess: () => {
                setEditingSession(null);
            },
        });
    };

    const handleDelete = (session) => {
        if (confirm(`Hapus sesi "${session.name}"? Log terkait juga akan dihapus.`)) {
            router.delete(route('pengaturan.whatsapp.session.destroy', session.id));
        }
    };

    const handleDisconnect = (session) => {
        if (confirm(`Putuskan koneksi "${session.name}"?`)) {
            router.post(route('pengaturan.whatsapp.disconnect'), { session_id: session.session_id });
        }
    };

    const handleTestSend = (e) => {
        e.preventDefault();
        router.post(route('pengaturan.whatsapp.test-send'), testSend);
    };

    const openTestModal = (session) => {
        setTestSend({ session_id: session.session_id, to: '', message: 'Test dari Koperasi' });
        setShowTestModal(true);
    };

    const openEditModal = (session) => {
        setEditingSession(session);
    };

    const StatusBadge = ({ status }) => {
        const colors = {
            sent: 'bg-green-100 text-green-800',
            failed: 'bg-red-100 text-red-800',
            pending: 'bg-yellow-100 text-yellow-800',
        };
        const icons = {
            sent: Check,
            failed: X,
            pending: Loader2,
        };
        const Icon = icons[status] || Loader2;
        return (
            <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${colors[status] || colors.pending}`}>
                <Icon className="w-3 h-3 mr-1" />
                {status.charAt(0).toUpperCase() + status.slice(1)}
            </span>
        );
    };

    const ConnectionStatus = ({ session }) => {
        if (session.phone_number) {
            return (
                <div className="flex items-center gap-2 text-green-600">
                    <Wifi className="w-4 h-4" />
                    <span className="text-sm font-medium">Terhubung</span>
                    <span className="text-xs text-gray-500">({session.phone_name || session.phone_number})</span>
                </div>
            );
        }
        return (
            <div className="flex items-center gap-2 text-gray-500">
                <WifiOff className="w-4 h-4" />
                <span className="text-sm">Belum terhubung</span>
            </div>
        );
    };

    return (
        <>
            <Head title="Pengaturan WhatsApp" />
            
            <div className="p-6 space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Pengaturan WhatsApp</h1>
                        <p className="text-gray-500 mt-1">Kelola sesi WhatsApp (Baileys), QR Code, dan log pengiriman</p>
                    </div>
                    <div className="flex items-center gap-2">
                        <label className="flex items-center gap-2 text-sm text-gray-600">
                            <input
                                type="checkbox"
                                checked={autoRefresh}
                                onChange={(e) => setAutoRefresh(e.target.checked)}
                                className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            />
                            Auto-refresh (10 detik)
                        </label>
                    </div>
                </div>

                {flash.status && (
                    <div className="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center justify-between">
                        <span>{flash.status}</span>
                        <button onClick={() => router.reload()} className="text-green-600 hover:text-green-800">×</button>
                    </div>
                )}
                {flash.errors && (
                    <div className="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                        <ul className="list-disc list-inside space-y-1">
                            {Object.values(flash.errors).flat().map((err, i) => <li key={i}>{err}</li>)}
                        </ul>
                    </div>
                )}

                <div className="border-b border-gray-200">
                    <nav className="flex gap-8" aria-label="Tabs">
                        <button
                            onClick={() => setActiveTab('sessions')}
                            className={`py-3 px-1 border-b-2 font-medium text-sm transition-colors ${
                                activeTab === 'sessions'
                                    ? 'border-blue-500 text-blue-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700'
                            }`}
                        >
                            <MessageSquare className="w-4 h-4 inline mr-1" />
                            Sesi ({sessions.length})
                        </button>
                        <button
                            onClick={() => setActiveTab('logs')}
                            className={`py-3 px-1 border-b-2 font-medium text-sm transition-colors ${
                                activeTab === 'logs'
                                    ? 'border-blue-500 text-blue-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700'
                            }`}
                        >
                            <Eye className="w-4 h-4 inline mr-1" />
                            Log Pengiriman ({logs.total})
                        </button>
                    </nav>
                </div>

                {activeTab === 'sessions' && (
                    <div className="space-y-6">
                        <div className="flex items-center justify-between">
                            <h2 className="text-lg font-semibold">Daftar Sesi WhatsApp</h2>
                            <button
                                onClick={() => { createForm.reset(); setShowCreateModal(true); }}
                                className="btn-primary flex items-center gap-2"
                            >
                                <Plus className="w-4 h-4" />
                                Tambah Sesi
                            </button>
                        </div>

                        {sessions.length === 0 ? (
                            <div className="text-center py-12 text-gray-500">
                                <MessageSquare className="w-12 h-12 mx-auto mb-4 text-gray-300" />
                                <p>Belum ada sesi WhatsApp. Klik "Tambah Sesi" untuk memulai.</p>
                            </div>
                        ) : (
                            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                {sessions.map((session) => (
                                    <div key={session.id} className="bg-white border border-gray-200 rounded-lg p-5 shadow-sm hover:shadow-md transition-shadow">
                                        <div className="flex items-start justify-between mb-3">
                                            <div className="flex items-center gap-2">
                                                <QrCode className="w-6 h-6 text-blue-600" />
                                                <div>
                                                    <h3 className="font-semibold text-gray-900">{session.name}</h3>
                                                    <p className="text-xs text-gray-500">ID: {session.session_id}</p>
                                                </div>
                                            </div>
                                            <span className={`px-2 py-0.5 rounded text-xs font-medium ${session.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'}`}>
                                                {session.is_active ? 'Aktif' : 'Nonaktif'}
                                            </span>
                                        </div>

                                        {session.description && (
                                            <p className="text-sm text-gray-600 mb-3 line-clamp-2">{session.description}</p>
                                        )}

                                        <ConnectionStatus session={session} />

                                        {session.last_connected_at && (
                                            <p className="text-xs text-gray-400 mt-2">
                                                Terakhir terhubung: {session.last_connected_at}
                                            </p>
                                        )}

                                        <div className="mt-4 flex flex-wrap gap-2">
                                            {!session.phone_number && session.is_active && (
                                                <button
                                                    onClick={() => fetchQrCode(session.session_id)}
                                                    className="btn-secondary flex-1 flex items-center justify-center gap-1 text-sm"
                                                >
                                                    <QrCode className="w-4 h-4" />
                                                    Scan QR
                                                </button>
                                            )}
                                            {session.phone_number && (
                                                <button
                                                    onClick={() => handleDisconnect(session)}
                                                    className="btn-secondary flex-1 flex items-center justify-center gap-1 text-sm text-red-600 hover:bg-red-50"
                                                >
                                                    <RefreshCw className="w-4 h-4" />
                                                    Reconnect
                                                </button>
                                            )}
                                            <button
                                                onClick={() => openTestModal(session)}
                                                className="btn-secondary flex-1 flex items-center justify-center gap-1 text-sm"
                                            >
                                                <Send className="w-4 h-4" />
                                                Test
                                            </button>
                                            <button
                                                onClick={() => openEditModal(session)}
                                                className="btn-ghost p-2"
                                                title="Edit"
                                            >
                                                <Edit className="w-4 h-4" />
                                            </button>
                                            {!session.is_default && (
                                                <button
                                                    onClick={() => handleDelete(session)}
                                                    className="btn-ghost p-2 text-red-600 hover:bg-red-50"
                                                    title="Hapus"
                                                >
                                                    <Trash2 className="w-4 h-4" />
                                                </button>
                                            )}
                                        </div>

                                        {session.is_default && (
                                            <div className="mt-3 pt-3 border-t border-gray-100 flex items-center gap-1 text-xs text-blue-600">
                                                <Check className="w-3 h-3" />
                                                <span>Sesi Default (notifikasi otomatis)</span>
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>
                        )}

                        {showCreateModal && (
                            <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                                <div className="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                                    <div className="p-5 border-b border-gray-200 flex items-center justify-between">
                                        <h3 className="text-lg font-semibold">Tambah Sesi WhatsApp Baru</h3>
                                        <button onClick={() => setShowCreateModal(false)} className="text-gray-400 hover:text-gray-600">×</button>
                                    </div>
                                    <form onSubmit={handleCreate} className="p-5 space-y-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Session ID</label>
                                            <input
                                                type="text"
                                                {...createForm.register('session_id')}
                                                className={`input ${createForm.errors.session_id ? 'border-red-500' : ''}`}
                                                placeholder="misal: notifikasi_utama"
                                                maxLength={50}
                                                required
                                            />
                                            {createForm.errors.session_id && <p className="text-red-500 text-sm mt-1">{createForm.errors.session_id}</p>}
                                            <p className="text-xs text-gray-500 mt-1">Huruf kecil, angka, underscore, dash saja. Unik per sesi.</p>
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Nama Tampilan</label>
                                            <input
                                                type="text"
                                                {...createForm.register('name')}
                                                className={`input ${createForm.errors.name ? 'border-red-500' : ''}`}
                                                placeholder="misal: Notifikasi Utama"
                                                maxLength={100}
                                                required
                                            />
                                            {createForm.errors.name && <p className="text-red-500 text-sm mt-1">{createForm.errors.name}</p>}
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                                            <textarea
                                                {...createForm.register('description')}
                                                className="input"
                                                rows={3}
                                                placeholder="Opsional: deskripsi fungsi sesi ini"
                                            />
                                        </div>
                                        <label className="flex items-center gap-2">
                                            <input
                                                type="checkbox"
                                                {...createForm.register('is_default')}
                                                className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                            />
                                            <span className="text-sm text-gray-700">Jadikan sesi default (notifikasi otomatis)</span>
                                        </label>
                                        <div className="flex justify-end gap-3 pt-4">
                                            <button type="button" onClick={() => setShowCreateModal(false)} className="btn-secondary">Batal</button>
                                            <button type="submit" className="btn-primary" disabled={createForm.processing}>
                                                {createForm.processing ? 'Membuat...' : 'Buat Sesi'}
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        )}

                        {editingSession && (
                            <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                                <div className="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                                    <div className="p-5 border-b border-gray-200 flex items-center justify-between">
                                        <h3 className="text-lg font-semibold">Edit Sesi: {editingSession.name}</h3>
                                        <button onClick={() => setEditingSession(null)} className="text-gray-400 hover:text-gray-600">×</button>
                                    </div>
                                    <form onSubmit={handleUpdate} className="p-5 space-y-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Nama Tampilan</label>
                                            <input
                                                type="text"
                                                {...editForm.register('name')}
                                                className={`input ${editForm.errors.name ? 'border-red-500' : ''}`}
                                                required
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                                            <textarea
                                                {...editForm.register('description')}
                                                className="input"
                                                rows={3}
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <label className="flex items-center gap-2">
                                                <input
                                                    type="checkbox"
                                                    {...editForm.register('is_default')}
                                                    className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                                />
                                                <span className="text-sm text-gray-700">Sesi default</span>
                                            </label>
                                            <label className="flex items-center gap-2">
                                                <input
                                                    type="checkbox"
                                                    {...editForm.register('is_active')}
                                                    className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                                />
                                                <span className="text-sm text-gray-700">Aktif</span>
                                            </label>
                                        </div>
                                        <div className="flex justify-end gap-3 pt-4">
                                            <button type="button" onClick={() => setEditingSession(null)} className="btn-secondary">Batal</button>
                                            <button type="submit" className="btn-primary" disabled={editForm.processing}>
                                                {editForm.processing ? 'Menyimpan...' : 'Simpan'}
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        )}

                        {showTestModal && (
                            <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                                <div className="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                                    <div className="p-5 border-b border-gray-200 flex items-center justify-between">
                                        <h3 className="text-lg font-semibold">Test Kirim Pesan</h3>
                                        <button onClick={() => setShowTestModal(false)} className="text-gray-400 hover:text-gray-600">×</button>
                                    </div>
                                    <form onSubmit={handleTestSend} className="p-5 space-y-4">
                                        <input type="hidden" name="session_id" value={testSend.session_id} />
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Nomor Tujuan</label>
                                            <input
                                                type="tel"
                                                name="to"
                                                value={testSend.to}
                                                onChange={(e) => setTestSend({...testSend, to: e.target.value})}
                                                className="input"
                                                placeholder="081234567890"
                                                required
                                            />
                                            <p className="text-xs text-gray-500 mt-1">Format: 08xxxxxxx atau +628xxxxxxx</p>
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Pesan</label>
                                            <textarea
                                                name="message"
                                                value={testSend.message}
                                                onChange={(e) => setTestSend({...testSend, message: e.target.value})}
                                                className="input"
                                                rows={4}
                                                required
                                            />
                                        </div>
                                        <div className="flex justify-end gap-3 pt-4">
                                            <button type="button" onClick={() => setShowTestModal(false)} className="btn-secondary">Batal</button>
                                            <button type="submit" className="btn-primary" disabled={router.processing}>
                                                <Send className="w-4 h-4 inline mr-1" />
                                                Kirim Test
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        )}
                    </div>
                )}

                {showQrModal && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50" onClick={closeQrModal}>
                        <div className="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6 text-center" onClick={e => e.stopPropagation()}>
                            <h3 className="text-lg font-semibold mb-4">Scan QR Code</h3>
                            {qrLoading ? (
                                <Loader2 className="w-8 h-8 animate-spin mx-auto text-blue-600" />
                            ) : qrCodeData ? (
                                <img src={qrCodeData} alt="QR Code" className="mx-auto border rounded p-4" style={{width: '256px', height: '256px'}} />
                            ) : (
                                <p className="text-red-600">QR tidak tersedia</p>
                            )}
                            <p className="text-sm text-gray-500 mt-4">Buka WhatsApp → Settings → Linked Devices → Link a Device</p>
                            <button onClick={closeQrModal} className="btn-secondary mt-4">Tutup</button>
                        </div>
                    </div>
                )}

                {activeTab === 'logs' && (
                    <div className="space-y-6">
                        <div className="flex flex-wrap gap-4 items-end">
                            <div className="flex-1 min-w-[200px]">
                                <label className="block text-sm font-medium text-gray-700 mb-1">Sesi</label>
                                <select
                                    {...logFilters.register('session_id')}
                                    className="input"
                                >
                                    <option value="">Semua Sesi</option>
                                    {sessions.map(s => <option key={s.session_id} value={s.session_id}>{s.name}</option>)}
                                </select>
                            </div>
                            <div className="flex-1 min-w-[150px]">
                                <label className="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select
                                    {...logFilters.register('status')}
                                    className="input"
                                >
                                    <option value="">Semua</option>
                                    {statusOptions.map(s => <option key={s} value={s}>{s.charAt(0).toUpperCase() + s.slice(1)}</option>)}
                                </select>
                            </div>
                            <div className="flex-1 min-w-[150px]">
                                <label className="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                                <input
                                    type="date"
                                    {...logFilters.register('date_from')}
                                    className="input"
                                />
                            </div>
                            <div className="flex-1 min-w-[150px]">
                                <label className="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                                <input
                                    type="date"
                                    {...logFilters.register('date_to')}
                                    className="input"
                                />
                            </div>
                            <div className="flex-1 min-w-[200px]">
                                <label className="block text-sm font-medium text-gray-700 mb-1">Cari</label>
                                <input
                                    type="text"
                                    {...logFilters.register('search')}
                                    className="input"
                                    placeholder="Nomor atau pesan..."
                                />
                            </div>
                            <button
                                onClick={() => logFilters.get(route('pengaturan.whatsapp'), { replace: true })}
                                className="btn-primary h-10"
                            >
                                Filter
                            </button>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead>
                                    <tr className="border-b border-gray-200 bg-gray-50">
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sesi</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nomor</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Referensi</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pesan</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Error</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200">
                                    {logs.data.length === 0 ? (
                                        <tr>
                                            <td colSpan={7} className="px-4 py-8 text-center text-gray-500">
                                                <MessageSquare className="w-8 h-8 mx-auto mb-2 text-gray-300" />
                                                <p>Tidak ada log pengiriman</p>
                                            </td>
                                        </tr>
                                    ) : (
                                        logs.data.map((log) => (
                                            <tr key={log.id} className="hover:bg-gray-50">
                                                <td className="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">{log.created_at}</td>
                                                <td className="px-4 py-3 text-sm text-gray-900 font-medium">{log.session_name}</td>
                                                <td className="px-4 py-3 text-sm text-gray-700 font-mono">{log.to}</td>
                                                <td className="px-4 py-3"><StatusBadge status={log.status} /></td>
                                                <td className="px-4 py-3 text-sm text-gray-500">
                                                    {log.reference_type && log.reference_id && (
                                                        <span className="font-mono text-xs">{log.reference_type.split('\\').pop()}:{log.reference_id}</span>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 text-sm text-gray-600 max-w-xs truncate block">{log.message}</td>
                                                <td className="px-4 py-3 text-sm text-red-600 max-w-xs truncate block">{log.error || '-'}</td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {logs.last_page > 1 && (
                            <div className="flex items-center justify-center gap-2">
                                {logs.current_page > 1 && (
                                    <button
                                        onClick={() => logFilters.get(route('pengaturan.whatsapp', { page: logs.current_page - 1 }), { replace: true })}
                                        className="btn-secondary text-sm"
                                    >
                                        Sebelumnya
                                    </button>
                                )}
                                <span className="text-sm text-gray-600">
                                    Halaman {logs.current_page} dari {logs.last_page} ({logs.total} total)
                                </span>
                                {logs.current_page < logs.last_page && (
                                    <button
                                        onClick={() => logFilters.get(route('pengaturan.whatsapp', { page: logs.current_page + 1 }), { replace: true })}
                                        className="btn-secondary text-sm"
                                    >
                                        Selanjutnya
                                    </button>
                                )}
                            </div>
                        )}
                    </div>
                )}
            </div>
        </>
    );
}