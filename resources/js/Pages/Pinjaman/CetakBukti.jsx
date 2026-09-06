import { Head } from '@inertiajs/react';
import { Printer } from 'lucide-react';

function formatRupiah(n) {
    return 'Rp ' + Number(n ?? 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });
}

function formatTanggal(iso) {
    if (!iso) return '-';
    const d = new Date(iso);
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
}

export default function CetakBukti({ pinjaman, angsuran, totals }) {
    function handlePrint() {
        window.print();
    }

    const docNo = `BUKTI-PJM/${pinjaman.anggota.no_anggota}/${pinjaman.id}`;
    const tglCetak = formatTanggal(new Date().toISOString().slice(0, 10));
    const ketuaNama = pinjaman.disetujui_oleh_ketua_nama ?? 'Ketua Koperasi';
    const verificationUrl = pinjaman.verification_url ?? `${window.location.origin}/verifikasi/bukti/${pinjaman.id}`;

    return (
        <>
            <Head title={`Bukti Peminjaman - ${pinjaman.anggota.nama}`} />

            <style>{`
                @media print {
                    body { background: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                    .no-print { display: none !important; }
                    .print-area { box-shadow: none !important; border: none !important; border-radius: 0 !important; padding: 0 !important; }
                    @page { margin: 2cm 2cm; }
                }
                .tnr { font-family: 'Times New Roman', Times, serif; }
            `}</style>

            <div className="min-h-screen bg-white py-8 px-4" style={{ fontFamily: "'Times New Roman', Times, serif" }}>
                <div className="max-w-4xl mx-auto no-print mb-4 flex justify-between items-center">
                    <h1 className="text-lg font-semibold text-slate-700">Bukti Peminjaman</h1>
                    <button
                        onClick={handlePrint}
                        className="inline-flex items-center gap-2 px-4 py-2 bg-brand-green text-white text-sm font-semibold rounded-xl hover:bg-brand-green/90"
                    >
                        <Printer size={16} />
                        Cetak / Simpan PDF
                    </button>
                </div>

                <div className="print-area max-w-4xl mx-auto bg-white p-8 border border-slate-200" style={{ fontFamily: "'Times New Roman', Times, serif" }}>
                    {/* ====== HEADER ====== */}
                    <div style={{ display: 'table', width: '100%', borderBottom: '2px solid #000', paddingBottom: '14px', marginBottom: '22px' }}>
                        <div style={{ display: 'table-row' }}>
                            <div style={{ display: 'table-cell', verticalAlign: 'middle', width: '80px', paddingRight: '18px' }}>
                                <img src="/images/logo.png" alt="Logo" style={{ width: '72px', height: '72px' }} />
                            </div>
                            <div style={{ display: 'table-cell', verticalAlign: 'middle', textAlign: 'center' }}>
                                <h1 className="tnr" style={{ fontSize: '18pt', fontWeight: 700, color: '#000', margin: '0 0 2px 0', letterSpacing: '1.2px', textTransform: 'uppercase' }}>KOPERASI KARYAWAN</h1>
                                <p className="tnr" style={{ fontSize: '9pt', color: '#4b5563', margin: '0 0 8px 0', fontStyle: 'italic', letterSpacing: '0.4px' }}>Simpan Pinjam &mdash; Anggota Koperasi</p>
                                <p className="tnr" style={{ fontSize: '14pt', fontWeight: 700, color: '#000', margin: '0 0 4px 0', textTransform: 'uppercase', letterSpacing: '1.8px', textDecoration: 'underline' }}>Bukti Peminjaman Dana</p>
                                <p className="tnr" style={{ fontSize: '10pt', color: '#000', margin: 0, fontWeight: 400, fontStyle: 'italic' }}>Nomor: {docNo}</p>
                            </div>
                        </div>
                    </div>

                    {/* ====== DATA ANGGOTA & DETAIL PINJAMAN ====== */}
                    <section style={{ marginBottom: '18px' }}>
                        <div className="tnr" style={{ fontSize: '11pt', fontWeight: 700, color: '#000', textTransform: 'uppercase', letterSpacing: '1.2px', background: '#f0f0f0', borderLeft: '4px solid #000', borderRight: '1px solid #000', borderTop: '1px solid #000', borderBottom: '1px solid #000', padding: '5px 10px', marginBottom: '10px' }}>
                            I. Data Anggota & Detail Pinjaman
                        </div>
                        <div style={{ display: 'table', width: '100%', borderCollapse: 'collapse', fontSize: '12pt', tableLayout: 'fixed' }}>
                            <InfoGridRow>
                                <InfoLabel>No. Anggota</InfoLabel>
                                <InfoValue>{pinjaman.anggota.no_anggota}</InfoValue>
                                <InfoSep />
                                <InfoLabel>Nominal Pinjaman</InfoLabel>
                                <InfoValue className="amount-large">{formatRupiah(pinjaman.nominal)}</InfoValue>
                            </InfoGridRow>
                            <InfoGridRow>
                                <InfoLabel>No. Karyawan</InfoLabel>
                                <InfoValue>{pinjaman.anggota.no_karyawan}</InfoValue>
                                <InfoSep />
                                <InfoLabel>Terbilang</InfoLabel>
                                <InfoValueNormal>{pinjaman.terbilang}</InfoValueNormal>
                            </InfoGridRow>
                            <InfoGridRow>
                                <InfoLabel>Nama Lengkap</InfoLabel>
                                <InfoValue>{pinjaman.anggota.nama}</InfoValue>
                                <InfoSep />
                                <InfoLabel>Jangka Waktu</InfoLabel>
                                <InfoValue>{pinjaman.tenor_bulan} Bulan</InfoValue>
                            </InfoGridRow>
                            <InfoGridRow>
                                <InfoLabel>Cabang</InfoLabel>
                                <InfoValue>{pinjaman.anggota.cabang}</InfoValue>
                                <InfoSep />
                                <InfoLabel>Suku Bunga</InfoLabel>
                                <InfoValue>{pinjaman.persentase_bunga}% per Bulan (Menurun)</InfoValue>
                            </InfoGridRow>
                            <InfoGridRow>
                                <InfoLabel>Jabatan</InfoLabel>
                                <InfoValueNormal>{pinjaman.anggota.jabatan}</InfoValueNormal>
                                <InfoSep />
                                <InfoLabel>Tanggal Pengajuan</InfoLabel>
                                <InfoValue>{formatTanggal(pinjaman.tanggal_pengajuan)}</InfoValue>
                            </InfoGridRow>
                            <InfoGridRow>
                                <InfoLabel>Unit Bisnis</InfoLabel>
                                <InfoValueNormal>{pinjaman.anggota.unit_bisnis}</InfoValueNormal>
                                <InfoSep />
                                <InfoLabel>Tanggal Pencairan</InfoLabel>
                                <InfoValue>{formatTanggal(pinjaman.tanggal_cair) || '-'}</InfoValue>
                            </InfoGridRow>
                            <InfoGridRow>
                                <InfoLabel>Keperluan</InfoLabel>
                                <InfoValueNormal style={{ paddingRight: '0' }}>{pinjaman.keperluan || '-'}</InfoValueNormal>
                            </InfoGridRow>
                        </div>
                    </section>

                    {/* ====== REKENING TUJUAN ====== */}
                    <section style={{ marginBottom: '18px' }}>
                        <div className="tnr" style={{ fontSize: '11pt', fontWeight: 700, color: '#000', textTransform: 'uppercase', letterSpacing: '1.2px', background: '#f0f0f0', borderLeft: '4px solid #000', borderRight: '1px solid #000', borderTop: '1px solid #000', borderBottom: '1px solid #000', padding: '5px 10px', marginBottom: '10px' }}>
                            II. Rekening Tujuan Pencairan
                        </div>
                        <div style={{ border: '1px solid #000', padding: '12px 16px', background: '#fafafa' }}>
                            <p className="tnr" style={{ fontSize: '13pt', fontWeight: 700, color: '#000', margin: '0 0 6px 0' }}>{pinjaman.rekening.bank || '-'}</p>
                            <p style={{ fontSize: '12pt', color: '#000', margin: '2px 0' }}><strong>No. Rekening:</strong> {pinjaman.rekening.no_rekening || '-'}</p>
                            <p style={{ fontSize: '12pt', color: '#000', margin: '2px 0' }}><strong>Atas Nama:</strong> {pinjaman.rekening.atas_nama || '-'}</p>
                        </div>
                    </section>

                    {/* ====== JADWAL ANGSURAN ====== */}
                    <section style={{ marginBottom: '18px' }}>
                        <div className="tnr" style={{ fontSize: '11pt', fontWeight: 700, color: '#000', textTransform: 'uppercase', letterSpacing: '1.2px', background: '#f0f0f0', borderLeft: '4px solid #000', borderRight: '1px solid #000', borderTop: '1px solid #000', borderBottom: '1px solid #000', padding: '5px 10px', marginBottom: '10px' }}>
                            III. Jadwal Angsuran
                        </div>
                        <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: '11pt', border: '1.5px solid #000' }}>
                            <thead>
                                <tr style={{ background: '#000' }}>
                                    <th style={{ width: '8%', color: 'white', fontWeight: 700, padding: '8px 8px', textAlign: 'center', border: '1px solid #000', fontSize: '10pt', letterSpacing: '0.5px', textTransform: 'uppercase' }}>No</th>
                                    <th style={{ width: '24%', color: 'white', fontWeight: 700, padding: '8px 8px', textAlign: 'center', border: '1px solid #000', fontSize: '10pt', letterSpacing: '0.5px', textTransform: 'uppercase' }}>Tanggal Jatuh Tempo</th>
                                    <th style={{ width: '22%', color: 'white', fontWeight: 700, padding: '8px 8px', textAlign: 'center', border: '1px solid #000', fontSize: '10pt', letterSpacing: '0.5px', textTransform: 'uppercase' }}>Pokok</th>
                                    <th style={{ width: '22%', color: 'white', fontWeight: 700, padding: '8px 8px', textAlign: 'center', border: '1px solid #000', fontSize: '10pt', letterSpacing: '0.5px', textTransform: 'uppercase' }}>Bunga</th>
                                    <th style={{ width: '24%', color: 'white', fontWeight: 700, padding: '8px 8px', textAlign: 'center', border: '1px solid #000', fontSize: '10pt', letterSpacing: '0.5px', textTransform: 'uppercase' }}>Total Bayar</th>
                                </tr>
                            </thead>
                            <tbody>
                                {angsuran.map((a) => (
                                    <tr key={a.cicilan_ke}>
                                        <td style={{ padding: '7px 8px', border: '0.75px solid #000', color: '#000', textAlign: 'center' }}>{a.cicilan_ke}</td>
                                        <td style={{ padding: '7px 8px', border: '0.75px solid #000', color: '#000', textAlign: 'center' }}>{a.tanggal_jatuh_tempo}</td>
                                        <td style={{ padding: '7px 8px', border: '0.75px solid #000', color: '#000', textAlign: 'right' }}>{formatRupiah(a.nominal_pokok)}</td>
                                        <td style={{ padding: '7px 8px', border: '0.75px solid #000', color: '#000', textAlign: 'right' }}>{formatRupiah(a.nominal_bunga)}</td>
                                        <td style={{ padding: '7px 8px', border: '0.75px solid #000', color: '#000', textAlign: 'right' }}>{formatRupiah(a.total_bayar)}</td>
                                    </tr>
                                ))}
                            </tbody>
                            <tfoot>
                                <tr style={{ background: '#f0f0f0' }}>
                                    <td colSpan={2} style={{ borderTop: '2px solid #000', fontWeight: 700, color: '#000', padding: '7px 8px', textAlign: 'center', fontSize: '11.5pt' }}>TOTAL</td>
                                    <td style={{ borderTop: '2px solid #000', fontWeight: 700, color: '#000', padding: '7px 8px', textAlign: 'right', fontSize: '11.5pt' }}>{formatRupiah(totals.pokok)}</td>
                                    <td style={{ borderTop: '2px solid #000', fontWeight: 700, color: '#000', padding: '7px 8px', textAlign: 'right', fontSize: '11.5pt' }}>{formatRupiah(totals.bunga)}</td>
                                    <td style={{ borderTop: '2px solid #000', fontWeight: 700, color: '#000', padding: '7px 8px', textAlign: 'right', fontSize: '11.5pt' }}>{formatRupiah(totals.angsuran)}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </section>

                    {/* ====== CATATAN ====== */}
                    <section style={{ marginBottom: '18px' }}>
                        <div style={{ border: '1px solid #000', padding: '10px 14px', fontSize: '10pt', color: '#000', lineHeight: 1.6, background: '#fafafa', fontStyle: 'italic' }}>
                            <strong>Catatan:</strong> Angsuran wajib dibayar paling lambat pada tanggal jatuh tempo setiap bulannya. Keterlambatan pembayaran akan dikenai denda sesuai ketentuan Anggaran Dasar dan Anggaran Rumah Tangga Koperasi. Dokumen ini dicetak melalui sistem dan merupakan bukti resmi peminjaman.
                        </div>
                    </section>

                    {/* ====== TANDA TANGAN KETUA + QR CODE ====== */}
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end', marginTop: '35px' }}>
                        {/* QR Code di kiri */}
                        <div style={{ width: '50%', textAlign: 'left', paddingBottom: '10px' }}>
                            <div className="tnr" style={{ fontSize: '7pt', color: '#666', marginBottom: '4px', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Scan untuk Verifikasi</div>
                            <div id="qr-code" style={{ width: '60px', height: '60px' }}></div>
                            <div className="tnr" style={{ fontSize: '5.5pt', color: '#999', marginTop: '2px' }}>
                                {new URL(verificationUrl).host}/verifikasi/bukti/{pinjaman.id}
                            </div>
                        </div>
                        {/* Tanda tangan ketua di kanan */}
                        <div style={{ width: '50%', textAlign: 'center', paddingRight: '20px' }}>
                            <div className="tnr" style={{ fontSize: '10pt', color: '#000', fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.8px', paddingBottom: '75px' }}>Ketua Koperasi</div>
                            <span className="tnr" style={{ fontSize: '11pt', fontWeight: 700, color: '#000', borderTop: '1.5px solid #000', paddingTop: '8px', display: 'block', textTransform: 'uppercase' }}>{ketuaNama}</span>
                            <span className="tnr" style={{ fontSize: '9pt', color: '#000', marginTop: '3px', display: 'block', fontStyle: 'italic' }}>Ketua</span>
                        </div>
                    </div>

                    {/* ====== FOOTER ====== */}
                    <div style={{ textAlign: 'center', marginTop: '22px', paddingTop: '10px', borderTop: '1px solid #000', fontSize: '9pt', color: '#000', letterSpacing: '0.4px', fontStyle: 'italic' }}>
                        Dokumen ini dicetak otomatis oleh sistem Koperasi Karyawan pada {tglCetak} &bull; {docNo}
                    </div>
                </div>
            </div>

            <script dangerouslySetInnerHTML={{
                __html: `
                    // Generate QR code client-side for preview
                    (function() {
                        var url = "${verificationUrl}";
                        var qrDiv = document.getElementById('qr-code');
                        if (!qrDiv) return;
                        // Simple QR code using Google Charts API for preview
                        qrDiv.innerHTML = '<img src="https://chart.googleapis.com/chart?cht=qr&chl=' + encodeURIComponent(url) + '&chs=60x60&chld=L|2" alt="QR Code" style="width:60px;height:60px;">';
                    })();
                `
            }} />
        </>
    );
}

function InfoGridRow({ children }) {
    return <div style={{ display: 'table-row' }}>{children}</div>;
}
function InfoLabel({ children }) {
    return <span style={{ display: 'table-cell', padding: '7px 16px 7px 0', verticalAlign: 'top', width: '28%', color: '#000', fontWeight: 400, whiteSpace: 'nowrap', paddingRight: '16px' }}>{children}</span>;
}
function InfoValue({ children, className }) {
    return <span style={{ display: 'table-cell', padding: '7px 16px 7px 0', verticalAlign: 'top', width: '22%', color: '#000', fontWeight: 700, whiteSpace: 'nowrap' }} className={className}>{children}</span>;
}
function InfoValueNormal({ children, style }) {
    return <span style={{ display: 'table-cell', padding: '7px 16px 7px 0', verticalAlign: 'top', width: '22%', color: '#000', fontWeight: 400, whiteSpace: 'nowrap', ...style }}>{children}</span>;
}
function InfoSep() {
    return <span style={{ display: 'table-cell', width: '4%' }} />;
}