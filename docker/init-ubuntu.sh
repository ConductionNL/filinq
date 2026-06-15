apt update;
apt install npm composer php-gd php-zip tesseract-ocr tesseract-ocr-nld tesseract-ocr-eng tesseract-ocr-deu tesseract-ocr-fra -y

cd /home/ubuntu/app
npm i
npm run dev

composer install