# Folhagens Casa Kotti

`foliage-left.svg` e `foliage-right.svg` derivam dos grupos originais `ramo-esquerda` e `ramo-direita`. Os paths e transforms do desenho foram preservados. Cada SVG usa o viewBox do envelope da tinta, `overflow="hidden"` e `preserveAspectRatio` ancorado no canto externo inferior.

A composição no site é um enquadramento: colunas laterais (`.ck-foliage--left` / `--right`) com largura `--ck-foliage-side`, centro limpo e `overflow: hidden` na camada. A opacidade e a escala vêm dos tokens em `assets/css/main.css`.
