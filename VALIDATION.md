# Validação da entrega

Data: 15 de setembro de 2026.

## Composição SVG (tema 1.5.1)

No desktop os ramos ficam nos cantos da viewport larga (`24vw`, no máximo `20rem`), não num cartão 2:3 central.

## Função (já exercitada nesta entrega)

- Sintaxe PHP 8.3.6 e JavaScript.
- Cadastro, duplicata genérica, CSV, exclusão administrativa e callbacks de dados pessoais na instalação temporária WordPress 7.1 + SQLite.
- Integridade dos ZIP com `unzip -t`.

## Limitações reais

- Não houve acesso ao WordPress de produção.
- Capturas headless do Chrome registram a viewport; em 375 × 560 o rodapé fica abaixo da dobra e exige rolagem, como previsto.
- Não foi possível abrir o Safari do iPhone; as safe areas foram aplicadas via `viewport-fit=cover` e `env(safe-area-inset-*)`.
- A prévia estática não executa persistência.
