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

## Limitações reais

- Não houve acesso ao WordPress ou banco do servidor de produção; nada foi publicado ou ativado nele.
- A instalação local usou SQLite para teste isolado. Antes da produção, repita um cadastro em homologação com a versão de WordPress, PHP e banco usados pelo servidor.
- Logo e folhagens originais não foram fornecidos. As capturas mostram o wordmark provisório e nenhuma folhagem.
- O comportamento com grupos internos dos SVGs só pode ser refinado após receber os arquivos originais e inspecionar sua estrutura.
- A prévia estática não executa persistência; as verificações funcionais acima foram feitas na instalação WordPress temporária.
