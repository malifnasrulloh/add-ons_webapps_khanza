
fitur penggabungan berkas PDF hanya jalan di lingkungan ubuntu -- Ichsan
sudo apt-get install ghostscript poppler-utils

sudo mv /home/user/berkas_digital_perawatan/ /var/www/html/webapps/berkas_digital_klaim/

# Pastikan folder tmp ada
mkdir -p /var/www/html/webapps/berkas_digital_perawatan/tmp/

sudo chown -R www-data:www-data /var/www/html/webapps/berkas_digital_klaim/
sudo chmod -R 755 /var/www/html/webapps/berkas_digital_klaim/
sudo chmod -R 777 /var/www/html/webapps/berkas_digital_klaim/tmp/


