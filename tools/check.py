#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Pemeriksaan statis project Colorbox.

Menjalankan:
    python3 tools/check.py

Yang diperiksa:
1. sintaks seluruh berkas PHP (php -l),
2. setiap controller dan method yang dipakai routes/web.php benar-benar ada,
3. setiap nama route yang dipakai view/controller terdaftar di routes/web.php,
4. setiap view yang dirujuk controller, @extends, dan @include benar-benar ada,
5. tidak ada pemakaian @json (dilarang pada project ini),
6. kata yang tidak boleh dipakai pada teks antarmuka.
"""
import os
import re
import subprocess
import sys

AKAR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
salah = []


def berkas(ekstensi, *folder):
    hasil = []
    for f in folder:
        for akar, _, nama in os.walk(os.path.join(AKAR, f)):
            for n in nama:
                if n.endswith(ekstensi):
                    hasil.append(os.path.join(akar, n))
    return sorted(hasil)


# ------------------------------------------------------------------ 1. php -l
php = berkas('.php', 'app', 'config', 'database', 'routes', 'bootstrap', 'public', 'tools')
for f in php:
    jalan = subprocess.run(['php', '-l', f], capture_output=True, text=True)
    if jalan.returncode != 0:
        salah.append('sintaks PHP: ' + jalan.stdout.strip())
print('php -l: %d berkas diperiksa' % len(php))

# ------------------------------------------------ 2. controller dan methodnya
rute = open(os.path.join(AKAR, 'routes/web.php'), encoding='utf-8').read()
impor = {k.split('\\')[-1]: k for k in re.findall(r'use\s+(App\\Http\\Controllers\\[\w\\]+);', rute)}
pasangan = re.findall(r'\[(\w+)::class,\s*\'(\w+)\'\]', rute)
for kelas, method in pasangan:
    penuh = impor.get(kelas)
    if not penuh:
        salah.append('controller tidak diimpor di routes: ' + kelas)
        continue
    jalur = os.path.join(AKAR, penuh.replace('App\\', 'app/').replace('\\', '/') + '.php')
    if not os.path.isfile(jalur):
        salah.append('berkas controller hilang: ' + jalur)
        continue
    isi = open(jalur, encoding='utf-8').read()
    if not re.search(r'function\s+' + method + r'\s*\(', isi):
        salah.append('method hilang: %s@%s' % (kelas, method))
print('controller: %d pasangan route diperiksa' % len(pasangan))

# ------------------------------------------------------------ 3. nama route
nama_rute = set(re.findall(r"->name\('([\w\-\.]+)'\)", rute))
awalan = re.findall(r"->name\('(\w+\.)'\)->group|->name\('(\w+\.)'\)", rute)
# route di dalam group ->name('admin.') diberi awalan
nama_rute |= {'admin.' + n for n in nama_rute}
sumber = berkas('.php', 'app') + berkas('.blade.php', 'resources/views')
dipakai = []
for f in sumber:
    isi = open(f, encoding='utf-8').read()
    for n in re.findall(r"route\('([\w\-\.]+)'", isi):
        dipakai.append((f, n))
for f, n in dipakai:
    if n not in nama_rute:
        salah.append('nama route tidak ada: %s (dipakai di %s)' % (n, os.path.relpath(f, AKAR)))
print('route: %d pemakaian diperiksa, %d nama terdaftar' % (len(dipakai), len(nama_rute)))

# ------------------------------------------------------------------ 4. view
def ada_view(nama):
    jalur = os.path.join(AKAR, 'resources/views', nama.replace('.', '/') + '.blade.php')
    return os.path.isfile(jalur)


jumlah_view = 0
for f in berkas('.php', 'app'):
    isi = open(f, encoding='utf-8').read()
    for n in re.findall(r"view\('([\w\-\.]+)'", isi):
        jumlah_view += 1
        if not ada_view(n):
            salah.append('view hilang: %s (dipanggil %s)' % (n, os.path.relpath(f, AKAR)))
for f in berkas('.blade.php', 'resources/views'):
    isi = open(f, encoding='utf-8').read()
    for tag in ('extends', 'include', 'includeIf'):
        for n in re.findall(r"@%s\('([\w\-\.]+)'" % tag, isi):
            jumlah_view += 1
            if not ada_view(n):
                salah.append('view hilang: %s (@%s di %s)' % (n, tag, os.path.relpath(f, AKAR)))
print('view: %d rujukan diperiksa' % jumlah_view)

# ------------------------------------------------------- 5 dan 6. larangan
for f in berkas('.blade.php', 'resources/views'):
    isi = open(f, encoding='utf-8').read()
    if '@json' in isi:
        salah.append('memakai @json: ' + os.path.relpath(f, AKAR))
    for kata in ('gerai', 'kanal daring', 'perangkat bergerak', 'surel',
                 'Saya sudah membayar'):
        if re.search(r'\b' + kata + r'\b', isi, re.I):
            salah.append('kata terlarang "%s" di %s' % (kata, os.path.relpath(f, AKAR)))

# ------------------------------------------------------------------ hasil
if salah:
    print('\nDitemukan %d masalah:' % len(salah))
    for s in salah:
        print(' -', s)
    sys.exit(1)

print('\nSemua pemeriksaan lolos.')
