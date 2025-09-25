-- ========================================
-- PENERANGAN PENTING - SISTEM PENGUNDIAN
-- ========================================
-- 1. Admin Login: admin@badminton.com / admin123
-- 2. Password disimpan sebagai plain text (bukan bcrypt)
-- 3. Sistem mempunyai 4 jawatan: pengerusi, setiausaha, bendahari, jawatankuasa
-- 4. Setiap pengguna boleh undi 1 kali untuk setiap jawatan
-- 5. Admin boleh kawalan tarikh pengundian dan toggle buka/tutup
-- 6. Tiada akses keputusan untuk pengguna biasa
-- ========================================

-- Cipta pangkalan data
CREATE DATABASE IF NOT EXISTS pengundian_badminton;
USE pengundian_badminton;

-- Jadual pengguna
CREATE TABLE pengguna (
    id_pengguna int auto_increment primary key,
    nama varchar(100) not null,
    emel varchar(100) unique not null,
    kata_laluan varchar(255) not null,
    peranan enum('admin', 'pengguna') default 'pengguna'
);

-- Jadual calon
CREATE TABLE calon (
    id_calon int auto_increment primary key,
    nama_calon varchar(100) not null,
    penerangan_calon text,
    jawatan_calon enum('pengerusi', 'setiausaha', 'bendahari', 'jawatankuasa') not null,
    gambar_calon varchar(255),
    undi_calon int default 0
);

-- Jadual undi
CREATE TABLE undi (
    id_undi int auto_increment primary key,
    id_pengguna int not null,
    id_calon int not null,
    jawatan_calon varchar(20) not null,
    masa_undi timestamp default current_timestamp,
    foreign key (id_pengguna) references pengguna(id_pengguna) on delete cascade,
    foreign key (id_calon) references calon(id_calon) on delete cascade
);

-- Jadual tetapan
CREATE TABLE tetapan (
    id_tetapan int auto_increment primary key,
    kunci_tetapan varchar(50) unique not null,
    nilai_tetapan text
);

-- Masukkan pengguna admin lalai (kata laluan: admin123)
INSERT INTO pengguna (nama, emel, kata_laluan, peranan) VALUES 
('Admin', 'admin@badminton.com', 'admin123', 'admin');

-- Masukkan calon Pengerusi
INSERT INTO calon (nama_calon, penerangan_calon, jawatan_calon) VALUES 
('Justin Tan Song Kai', '5SE1 - Seorang pelajar berwawasan, berkarisma dan aktif dalam pelbagai aktiviti.', 'pengerusi'),
('Lucas Lau Jin Heng', '4SE2 - Pemimpin yang aktif dalam aktiviti kelab dan mampu mengurus pasukan dengan baik.', 'pengerusi'),
('Soo Yu Zhe', '5SA2 - Mempunyai kemahiran komunikasi yang baik dan berupaya memimpin dengan yakin.', 'pengerusi'),
('Clement Cheong Jia Jun', '5SA5 - Seorang yang berdisiplin dan sentiasa memberi idea baharu untuk kelab.', 'pengerusi'),
('Low Guan Wei', '5SB3 - Pemimpin muda yang berpengalaman dalam kerja berpasukan dan perancangan.', 'pengerusi'),

-- Masukkan calon Setiausaha
('Sim Tong Liang', '4SA3 - Cekap menguruskan dokumen dan berkemahiran dalam penyelarasan mesyuarat.', 'setiausaha'),
('Lau Meng Hong', '4SK2 - Bertanggungjawab, rajin dan aktif dalam pelbagai program sekolah.', 'setiausaha'),
('Jonas Lam Kar Wor', '5SB2 - Seorang yang teratur dan mampu menguruskan rekod dengan baik.', 'setiausaha'),
('Bryce Ooi Yu Xuan', '4SA3 - Komited dan sentiasa memberikan sumbangan dalam aktiviti kelab.', 'setiausaha'),
('Keoh Ying Zhe', '5SA6 - Mempunyai kemahiran organisasi dan kepimpinan yang tinggi.', 'setiausaha'),

-- Masukkan calon Bendahari
('Daniel Fong Kai Shung', '5SA2 - Teliti, amanah dan mahir dalam pengurusan kewangan.', 'bendahari'),
('Wong Yuan Jie', '4SA6 - Mempunyai kebolehan mengurus bajet dengan sistematik.', 'bendahari'),
('Arwin A/L Tharman', '4SK2 - Bertanggungjawab, jujur dan sentiasa menepati janji.', 'bendahari'),
('Victor Oon Guo Liang', '5SA5 - Cekap dalam mengurus dana dan berfikiran strategik.', 'bendahari'),
('Keoh Ying Zhe', '5SA6 - Mahir mengatur tugasan dan memastikan kelancaran program.', 'bendahari'),

-- Masukkan calon Jawatankuasa Lain
('Gan Khoon Tshen', '5SA1 - Aktif dan sentiasa membantu dalam pelaksanaan aktiviti.', 'jawatankuasa'),
('Terrence Teh Yu Zhe', '4SA1 - Bersemangat tinggi dan mempunyai kerjasama yang baik.', 'jawatankuasa'),
('Tan Ze Khai', '4SA2 - Kreatif dan komited dalam merancang aktiviti.', 'jawatankuasa'),
('Ong Zhi Yi', '3TA3 - Bertanggungjawab dan rajin menyumbang tenaga dalam program.', 'jawatankuasa'),
('Tan Ze Wooi', '3TB2 - Sentiasa memberikan idea dan sokongan kepada pasukan.', 'jawatankuasa'),
('Cheng Min Feng', '1T14 - Muda, bersemangat dan aktif dalam pelbagai aktiviti.', 'jawatankuasa'),
('Moo Chin Siang', '4SA6 - Konsisten hadir dan menyokong semua program.', 'jawatankuasa'),
('Lim Naphatsakorn Sen', '5SA4 - Mempunyai semangat kerjasama yang tinggi.', 'jawatankuasa'),
('Nicholas Lim Ding Yi', '4SA6 - Kreatif dan mampu membantu dalam pelbagai tugasan.', 'jawatankuasa'),
('Leonard Low Zhen Wei', '4SK2 - Berdisiplin dan komited terhadap tanggungjawab yang diberikan.', 'jawatankuasa');

-- Masukkan tetapan sistem lalai
INSERT INTO tetapan (kunci_tetapan, nilai_tetapan) VALUES 
('tarikh_pengundian', '{"mula":"2024-01-01","tamat":"2024-12-31"}');