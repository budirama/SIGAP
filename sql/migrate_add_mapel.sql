USE kuis_sd;

ALTER TABLE kisi_kisi ADD COLUMN mapel VARCHAR(100) NOT NULL DEFAULT '' AFTER jenjang;
ALTER TABLE soal ADD COLUMN mapel VARCHAR(100) NOT NULL DEFAULT '' AFTER jenjang;

-- Backfill data lama (dibuat sebelum kolom mapel ada) berdasarkan materi yang sudah diketahui.
UPDATE kisi_kisi SET mapel = 'IPA'
  WHERE mapel = '' AND materi IN ('Ekosistem', 'Sifat-sifat Benda', 'Sistem Peredaran Darah');
UPDATE kisi_kisi SET mapel = 'Matematika' WHERE mapel = '';

UPDATE soal s JOIN kisi_kisi k ON s.kisi_id = k.id SET s.mapel = k.mapel WHERE s.mapel = '';

CREATE INDEX idx_kisi_mapel ON kisi_kisi(mapel);
CREATE INDEX idx_soal_mapel ON soal(mapel);
