#!/usr/bin/env bash
set -euo pipefail

# Regenera los íconos PNG/ICO a partir de public/favicon.svg (el logo real
# de Merkamigo). Corre esto después de actualizar el logo (0.2 del TODO).
#
# Uso: ./scripts/generate-icons.sh

cd "$(dirname "$0")/.."

mkdir -p public/icons

npx --yes sharp-cli -i public/favicon.svg -o public/apple-touch-icon.png resize 180 180
npx --yes sharp-cli -i public/favicon.svg -o public/icons/icon-192.png resize 192 192
npx --yes sharp-cli -i public/favicon.svg -o public/icons/icon-512.png resize 512 512
npx --yes sharp-cli -i public/favicon.svg -o /tmp/merkamigo-favicon-32.png resize 32 32

php -r '
$pngData = file_get_contents("/tmp/merkamigo-favicon-32.png");
$header = pack("vvv", 0, 1, 1);
$dirEntry = pack("CCCCvvVV", 32, 32, 0, 0, 1, 32, strlen($pngData), 6 + 16);
file_put_contents("public/favicon.ico", $header.$dirEntry.$pngData);
'

rm -f /tmp/merkamigo-favicon-32.png

echo "Íconos regenerados en public/."
