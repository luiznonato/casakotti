# Validação da entrega

Data: 15 de setembro de 2026.

## Composição 6×9 (tema 1.4.8)

A página pública centra um palco 6×9 (1024×1536 e equivalentes). Os ramos originais ocupam cerca de um terço da largura em cada canto inferior, com o centro aberto. Sem `overflow` no palco de conteúdo; a camada `.ck-foliage-layer` recorta só a borda do quadro.

## Composição responsiva (tema 1.4.0)

Verificado em WordPress local nas larguras 320, 375, 390, 430, 768 e 1440 px, mais uma janela baixa 375 × 560.

- Os dois ramos originais formam moldura lateral a partir dos cantos inferiores; o ramo direito acompanha a borda direita.
- O viewBox foi ajustado ao envelope da tinta (sem esticar paths) para eliminar o vazio que concentrava os desenhos no rodapé.
- Posicionamento em wrappers separados da animação; recorte na camada `.ck-foliage-layer`; sem `position: fixed`.
- Logo menor no celular; títulos em Montserrat caixa alta.
- Campo, botão e consentimento permanecem legíveis no centro.
- Sem `overflow` no `body` para esconder largura; rolagem vertical permitida em tela baixa.
- Animação: rotação lenta na base, desligada com `prefers-reduced-motion`.

## Função (já exercitada nesta entrega)

- Sintaxe PHP 8.3.6 e JavaScript.
- Cadastro, duplicata genérica, CSV, exclusão administrativa e callbacks de dados pessoais na instalação temporária WordPress 7.1 + SQLite.
- Integridade dos ZIP com `unzip -t`.

## Limitações reais

- Não houve acesso ao WordPress de produção.
- Capturas headless do Chrome registram a viewport; em 375 × 560 o rodapé fica abaixo da dobra e exige rolagem, como previsto.
- Não foi possível abrir o Safari do iPhone; as safe areas foram aplicadas via `viewport-fit=cover` e `env(safe-area-inset-*)`.
- A prévia estática não executa persistência.
