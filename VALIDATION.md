# Validação da entrega

Data: 15 de setembro de 2026.

## Executado

- Sintaxe de todos os arquivos PHP com PHP 8.3.6.
- Sintaxe do JavaScript com Node.js 22.
- Instalação temporária, fora do repositório, em WordPress 7.1 com SQLite Database Integration 3.0.2.
- Ativação do tema e do plugin e criação da tabela via `dbDelta`.
- Resposta HTTP 200 da página inicial.
- Cadastro válido com confirmação da linha persistida.
- E-mail inválido e ausência de consentimento rejeitados.
- Cadastro duplicado sem segunda linha e sem revelar a existência do e-mail.
- Falha forçada de persistência retornando erro, sem falso sucesso.
- Bloqueio da tela administrativa para usuário com papel de assinante.
- Exclusão administrativa com permissão e nonce.
- Exportação CSV e neutralização de valor iniciado por `=`.
- Callbacks nativos de exportação e exclusão de dados pessoais.
- Integridade dos dois arquivos ZIP com `unzip -t`.
- Revisão visual desktop (1280 × 800), mobile (375 × 812) e viewport baixo (400 × 500).
- Ausência de rolagem horizontal, rolagem vertical natural, ordem de teclado, foco visível, checkbox desmarcado e mensagens de validação.
- Emulação de `prefers-reduced-motion`.
- SVG original inspecionado e sanitizado, sem scripts, eventos ou referências externas.
- Folhagens verificadas em desktop (1280 × 800): dois ramos com animações independentes de 11,4 s e 14,2 s, delays distintos e movimento restrito a `transform`.
- Folhagens verificadas em mobile (375 × 812): apenas um ramo menor, sem interferência no conteúdo e sem rolagem horizontal.
- Com `prefers-reduced-motion`, os ramos permanecem estáticos.
- Logo oficial verificado em desktop e celular, com símbolo e lettering completos, proporção intrínseca de 1237 × 752 e sem efeitos CSS.
- Montserrat variável carregada localmente em WOFF2 com resposta HTTP 200; peso computado 400 no H1.
- H1 e complemento da marca verificados em caixa alta.
- Ramo direito verificado com `display: block` e folhas reconhecíveis no celular, mantendo margem durante o movimento.
- No viewport móvel de 375 px, `scrollWidth` e `clientWidth` permaneceram em 375 px, com `window.scrollX` igual a zero.

## Limitações reais

- Não houve acesso ao WordPress ou banco do servidor de produção; nada foi publicado ou ativado nele.
- A instalação local usou SQLite para teste isolado. Antes da produção, repita um cadastro em homologação com a versão de WordPress, PHP e banco usados pelo servidor.
- O logo fornecido originalmente estava em JPEG sobre fundo branco. A integração remove apenas esse fundo e aplica o bege da identidade ao mesmo desenho; símbolo, lettering e proporções foram preservados.
- O SVG fornecido contém dois grupos principais, um por ramo. Cada ramo é animado como unidade para preservar o desenho original sem deformar as folhas.
- A prévia estática não executa persistência; as verificações funcionais acima foram feitas na instalação WordPress temporária.
