<?php
class GuestBook
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Simpan pesan (prepared statement)
    public function simpan(string $nama, string $email, string $pesan): bool
    {
        $sql = "INSERT INTO buku_tamu (nama, email, pesan) VALUES (:nama, :email, :pesan)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':nama'  => $nama,
            ':email' => $email,
            ':pesan' => $pesan,
        ]);
    }

    // Ambil semua pesan, terbaru di atas (prepared statement)
    public function semua(): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, nama, email, pesan, tanggal_kirim FROM buku_tamu ORDER BY tanggal_kirim DESC, id DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
