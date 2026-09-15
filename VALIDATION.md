# Validação da entrega

Data: 15 de setembro de 2026.

## Composição de fundo (tema 1.4.9)

O JPEG original 1024×1536 é o fundo de `.ck-page`. Mobile: `background-size: 100% auto; background-position: center bottom`. Desktop: `contain` centralizado. Sem `cover`, sem `100% 100%`, sem opacidade extra e sem ramos SVG.

## Composição responsiva (tema 1.4.0)

Capturas da prévia estática nas larguras 360, 390, 430 e 1440 px.

- Fundo JPEG original, inteiro, sem distorção e sem duplicar folhas.
- Folhas nos cantos inferiores, subindo pelas laterais, como no arquivo.
- Sem rolagem horizontal.
- Logo, textos, formulário e rodapé acima do fundo, clicáveis.

## Função (já exercitada nesta entrega)

- Sintaxe PHP 8.3.6 e JavaScript.
- Cadastro, duplicata genérica, CSV, exclusão administrativa e callbacks de dados pessoais na instalação temporária WordPress 7.1 + SQLite.
- Integridade dos ZIP com `unzip -t`.

## Limitações reais

- Não houve acesso ao WordPress de produção.
- Capturas headless do Chrome registram a viewport; em 375 × 560 o rodapé fica abaixo da dobra e exige rolagem, como previsto.
- Não foi possível abrir o Safari do iPhone; as safe areas foram aplicadas via `viewport-fit=cover` e `env(safe-area-inset-*)`.
- A prévia estática não executa persistência.
