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

export default function CetakBukti({ pinjaman, angsuran, totals, kota_ttd }) {
    function handlePrint() {
        window.print();
    }

    const docNo = pinjaman.nomor_dokumen ?? `BUKTI-PJM/${pinjaman.anggota.no_karyawan}/${pinjaman.id}`;
    const tglCetak = formatTanggal(new Date().toISOString().slice(0, 10));
    const tglTtd = formatTanggal(pinjaman.tanggal_cair) !== '-' ? formatTanggal(pinjaman.tanggal_cair) : tglCetak;
    const kota = kota_ttd ?? 'Banjarmasin';
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
                    <div style={{ display: 'table', width: '100%' }}>
                        <div style={{ display: 'table-row' }}>
                            <div style={{ display: 'table-cell', verticalAlign: 'middle', width: '80px', paddingRight: '16px' }}>
                                <img src="/images/logo.png" alt="Logo" style={{ width: '72px', height: '72px' }} />
                            </div>
                            <div style={{ display: 'table-cell', verticalAlign: 'middle', textAlign: 'center' }}>
                                <h1 className="tnr" style={{ fontSize: '17pt', fontWeight: 700, color: '#000', margin: 0, letterSpacing: '1px', textTransform: 'uppercase' }}>KOPERASI KARYAWAN</h1>
                                <p className="tnr" style={{ fontSize: '12pt', fontWeight: 700, color: '#000', margin: '2px 0', letterSpacing: '0.6px', textTransform: 'uppercase' }}>KARYA MANDIRI DUTA MALL BANJARMASIN</p>
                                <p className="tnr" style={{ fontSize: '9pt', color: '#333', margin: 0 }}>Jalan Jenderal Ahmad Yani KM 2 No. 98, Melayu</p>
                            </div>
                        </div>
                    </div>
                    <div style={{ borderTop: '3px double #000', margin: '10px 0 16px 0' }} />
                    <div style={{ textAlign: 'center', marginBottom: '18px' }}>
                        <p className="tnr" style={{ fontSize: '14pt', fontWeight: 700, color: '#000', margin: '0 0 4px 0', textTransform: 'uppercase', letterSpacing: '1.6px', textDecoration: 'underline' }}>Bukti Peminjaman Dana</p>
                        <p className="tnr" style={{ fontSize: '10pt', color: '#000', margin: 0, fontStyle: 'italic' }}>Nomor: {docNo}</p>
                    </div>

                    <p className="tnr" style={{ fontSize: '12pt', color: '#000', textAlign: 'justify', marginBottom: '16px' }}>
                        Dengan hormat,<br />
                        Berdasarkan pengajuan yang telah disetujui, bersama ini kami sampaikan bukti pencairan pinjaman dana kepada anggota di bawah ini:
                    </p>

                    <section style={{ marginBottom: '18px' }}>
                        <JudulBagian>I. Data Anggota &amp; Detail Pinjaman</JudulBagian>
                        <div style={{ display: 'table', width: '100%', fontSize: '12pt', tableLayout: 'fixed' }}>
                            <InfoGridRow>
                                <InfoLabel>No. Karyawan</InfoLabel>
                                <InfoValue>{pinjaman.anggota.no_karyawan}</InfoValue>
                            </InfoGridRow>
                            <InfoGridRow>
                                <InfoLabel>Nama Lengkap</InfoLabel>
                                <InfoValue>{pinjaman.anggota.nama}</InfoValue>
                            </InfoGridRow>
                            <InfoGridRow>
                                <InfoLabel>Cabang / Unit Bisnis</InfoLabel>
                                <InfoValueNormal>{pinjaman.anggota.cabang} / {pinjaman.anggota.unit_bisnis}</InfoValueNormal>
                            </InfoGridRow>
                            <InfoGridRow>
                                <InfoLabel>Jabatan</InfoLabel>
                                <InfoValueNormal>{pinjaman.anggota.jabatan}</InfoValueNormal>
                            </InfoGridRow>
                            <InfoGridRow>
                                <InfoLabel>Nominal Pinjaman</InfoLabel>
                                <InfoValue>{formatRupiah(pinjaman.nominal)}</InfoValue>
                            </InfoGridRow>
                            <InfoGridRow>
                                <InfoLabel>Jangka Waktu</InfoLabel>
                                <InfoValueNormal>{pinjaman.tenor_bulan} Bulan</InfoValueNormal>
                            </InfoGridRow>
                            <InfoGridRow>
                                <InfoLabel>Suku Bunga</InfoLabel>
                                <InfoValueNormal>{pinjaman.persentase_bunga}% per Bulan (Menurun)</InfoValueNormal>
                            </InfoGridRow>
                            <InfoGridRow>
                                <InfoLabel>Tanggal Pengajuan</InfoLabel>
                                <InfoValueNormal>{formatTanggal(pinjaman.tanggal_pengajuan)}</InfoValueNormal>
                            </InfoGridRow>
                            <InfoGridRow>
                                <InfoLabel>Tanggal Pencairan</InfoLabel>
                                <InfoValueNormal>{formatTanggal(pinjaman.tanggal_cair) || '-'}</InfoValueNormal>
                            </InfoGridRow>
                            <InfoGridRow>
                                <InfoLabel>Keperluan</InfoLabel>
                                <InfoValueNormal>{pinjaman.keperluan || '-'}</InfoValueNormal>
                            </InfoGridRow>
                        </div>
                        <div style={{ border: '1px solid #000', background: '#fafafa', padding: '8px 12px', fontStyle: 'italic', fontSize: '11pt', marginTop: '10px' }}>
                            Terbilang: {pinjaman.terbilang}
                        </div>
                    </section>

                    <section style={{ marginBottom: '18px' }}>
                        <JudulBagian>II. Rekening Tujuan Pencairan</JudulBagian>
                        <div style={{ border: '1px solid #000', padding: '10px 14px', background: '#fafafa' }}>
                            <p className="tnr" style={{ fontSize: '12.5pt', fontWeight: 700, color: '#000', margin: '0 0 4px 0' }}>{pinjaman.rekening.bank || '-'}</p>
                            <p style={{ fontSize: '11pt', color: '#000', margin: '2px 0' }}><strong>No. Rekening:</strong> {pinjaman.rekening.no_rekening || '-'}</p>
                            <p style={{ fontSize: '11pt', color: '#000', margin: '2px 0' }}><strong>Atas Nama:</strong> {pinjaman.rekening.atas_nama || '-'}</p>
                        </div>
                    </section>

                    <section style={{ marginBottom: '18px' }}>
                        <JudulBagian>III. Jadwal Angsuran</JudulBagian>
                        <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: '11pt' }}>
                            <thead>
                                <tr>
                                    <Th>No</Th>
                                    <Th>Tanggal Jatuh Tempo</Th>
                                    <Th align="right">Pokok</Th>
                                    <Th align="right">Bunga</Th>
                                    <Th align="right">Total Bayar</Th>
                                </tr>
                            </thead>
                            <tbody>
                                {angsuran.map((a) => (
                                    <tr key={a.cicilan_ke}>
                                        <Td center>{a.cicilan_ke}</Td>
                                        <Td center>{a.tanggal_jatuh_tempo}</Td>
                                        <Td align="right">{formatRupiah(a.nominal_pokok)}</Td>
                                        <Td align="right">{formatRupiah(a.nominal_bunga)}</Td>
                                        <Td align="right">{formatRupiah(a.total_bayar)}</Td>
                                    </tr>
                                ))}
                            </tbody>
                            <tfoot>
                                <tr style={{ background: '#f0f0f0', fontWeight: 700 }}>
                                    <Td center colSpan={2}>TOTAL</Td>
                                    <Td align="right">{formatRupiah(totals.pokok)}</Td>
                                    <Td align="right">{formatRupiah(totals.bunga)}</Td>
                                    <Td align="right">{formatRupiah(totals.angsuran)}</Td>
                                </tr>
                            </tfoot>
                        </table>
                    </section>

                    <section style={{ marginBottom: '18px' }}>
                        <div style={{ borderLeft: '3px solid #000', padding: '8px 0 8px 14px', fontSize: '10.5pt', color: '#000', lineHeight: 1.6, fontStyle: 'italic' }}>
                            <strong>Catatan:</strong> Angsuran wajib dibayar paling lambat pada tanggal jatuh tempo setiap bulannya.
                            Keterlambatan pembayaran mengikuti ketentuan Anggaran Dasar dan Anggaran Rumah Tangga koperasi.
                            Dokumen ini merupakan bukti resmi pencairan pinjaman.
                        </div>
                    </section>

                    <p className="tnr" style={{ fontSize: '12pt', color: '#000', textAlign: 'justify', margin: '16px 0 8px 0' }}>
                        Demikian bukti peminjaman ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana mestinya.
                    </p>

                    <div style={{ display: 'table', width: '100%', marginTop: '24px' }}>
                        <div style={{ display: 'table-row' }}>
                            <div style={{ display: 'table-cell', width: '50%', textAlign: 'center', verticalAlign: 'top' }}>
                                <div className="tnr" style={{ fontSize: '10pt', color: '#000', marginBottom: '4px' }}>{kota}, {tglTtd}<br />Penerima</div>
                                <div style={{ height: '80px' }} />
                                <span className="tnr" style={{ fontSize: '11pt', fontWeight: 700, color: '#000', borderTop: '1.5px solid #000', paddingTop: '6px', display: 'inline-block', minWidth: '200px', textTransform: 'uppercase' }}>{pinjaman.anggota.nama}</span>
                                <span className="tnr" style={{ fontSize: '9pt', color: '#000', marginTop: '2px', display: 'block', fontStyle: 'italic' }}>No. Karyawan: {pinjaman.anggota.no_karyawan}</span>
                            </div>
                            <div style={{ display: 'table-cell', width: '50%', textAlign: 'center', verticalAlign: 'top' }}>
                                <div className="tnr" style={{ fontSize: '10pt', color: '#000', marginBottom: '4px' }}>{kota}, {tglTtd}<br />Ketua Koperasi</div>
                                <div id="qr-code" style={{ width: '60px', height: '60px', margin: '6px auto' }}></div>
                                <div style={{ height: '24px' }} />
                                <span className="tnr" style={{ fontSize: '11pt', fontWeight: 700, color: '#000', borderTop: '1.5px solid #000', paddingTop: '6px', display: 'inline-block', minWidth: '200px', textTransform: 'uppercase' }}>{ketuaNama}</span>
                                <span className="tnr" style={{ fontSize: '9pt', color: '#000', marginTop: '2px', display: 'block', fontStyle: 'italic' }}>Ketua</span>
                                <div className="tnr" style={{ fontSize: '8pt', color: '#333', marginTop: '4px' }}>Pindai untuk verifikasi keaslian dokumen</div>
                            </div>
                        </div>
                    </div>

                    <div style={{ textAlign: 'center', marginTop: '20px', paddingTop: '8px', borderTop: '1px solid #000', fontSize: '9pt', color: '#000', fontStyle: 'italic' }}>
                        Dokumen ini diterbitkan oleh sistem Koperasi Karyawan pada {tglCetak} &bull; {docNo}
                    </div>
                </div>
            </div>

            <script dangerouslySetInnerHTML={{
                __html: `
                    (function() {
                        var url = "${verificationUrl}";
                        var qrDiv = document.getElementById('qr-code');
                        if (!qrDiv) return;
                        qrDiv.innerHTML = '<img src="https://chart.googleapis.com/chart?cht=qr&chl=' + encodeURIComponent(url) + '&chs=60x60&chld=L|2" alt="QR Code" style="width:60px;height:60px;">';
                    })();
                `
            }} />
        </>
    );
}

function JudulBagian({ children }) {
    return <div className="tnr" style={{ fontSize: '11.5pt', fontWeight: 700, color: '#000', textTransform: 'uppercase', letterSpacing: '1px', borderBottom: '2px solid #000', padding: '4px 0', marginBottom: '10px' }}>{children}</div>;
}
function InfoGridRow({ children }) {
    return <div style={{ display: 'table-row' }}>{children}</div>;
}
function InfoLabel({ children }) {
    return <span style={{ display: 'table-cell', padding: '5px 12px 5px 0', verticalAlign: 'top', width: '30%', color: '#000' }}>{children}</span>;
}
function InfoValue({ children }) {
    return <span style={{ display: 'table-cell', padding: '5px 12px 5px 0', verticalAlign: 'top', width: '67%', color: '#000', fontWeight: 700 }}>{children}</span>;
}
function InfoValueNormal({ children }) {
    return <span style={{ display: 'table-cell', padding: '5px 12px 5px 0', verticalAlign: 'top', width: '67%', color: '#000' }}>{children}</span>;
}
function Th({ children, align }) {
    return <th style={{ border: '1px solid #000', background: '#f0f0f0', padding: '6px 8px', textAlign: align ?? 'center', fontSize: '9.5pt', textTransform: 'uppercase', letterSpacing: '0.5px' }}>{children}</th>;
}
function Td({ children, align, center, colSpan }) {
    return <td colSpan={colSpan} style={{ border: '1px solid #000', padding: '6px 8px', color: '#000', textAlign: center ? 'center' : (align ?? 'left') }}>{children}</td>;
}
