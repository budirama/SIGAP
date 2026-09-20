USE kuis_sd;

ALTER TABLE soal
  ADD COLUMN visual_tipe ENUM('bangun_datar','hitung_benda','bandingkan_benda') NULL AFTER pembahasan,
  ADD COLUMN visual_data TEXT NULL AFTER visual_tipe;
