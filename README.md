# Casa Kotti — pré-lançamento

Tema WordPress e plugin independente para a página de pré-lançamento da Casa Kotti. O tema cuida da composição visual; o plugin armazena os interessados para que os dados sobrevivam a uma futura troca de tema.

## Estrutura

- `theme/casa-kotti/`: tema instalável.
- `plugin/casa-kotti-interesses/`: plugin instalável de captação e administração.
- `preview/`: prévia estática apenas para revisão visual.
- `dist/`: pacotes ZIP prontos para instalar, gerados a partir das pastas acima.

WordPress core, banco, uploads, credenciais e configurações do servidor não fazem parte deste repositório.

## Instalação

1. Em **Plugins > Adicionar plugin > Enviar plugin**, envie `dist/casa-kotti-interesses.zip` e ative.
2. Em **Aparência > Temas > Adicionar tema > Enviar tema**, envie `dist/casa-kotti.zip` e ative quando estiver pronto para substituir o tema atual.
3. Crie uma página (por exemplo, “Início”) e selecione-a em **Configurações > Leitura** como página inicial estática.
4. Crie e publique a política de privacidade aprovada. Selecione a mesma página:
   - em **Configurações > Privacidade**; e
   - em **Aparência > Personalizar > Casa Kotti — Pré-lançamento**.
5. Em **Aparência > Personalizar**, confirme o logo e configure textos, links, contato, formulário e animações. O logo oficial já acompanha o tema; um logo configurado no WordPress continua tendo prioridade.

O formulário permanece oculto e não aceita cadastros até que uma página de privacidade publicada esteja corretamente selecionada. Se houver e-mail de contato, ele aparece como alternativa. Instagram e demais links opcionais ficam ocultos enquanto estiverem vazios.

## Cadastros e privacidade

O formulário registra somente:

- e-mail;
- data e hora em UTC;
- caminho de origem no site;
- texto e hash da versão do consentimento aceito.

O checkbox começa desmarcado. A validação ocorre no servidor, com nonce, honeypot, limite de cinco tentativas por hora por identificador efêmero e restrição única de e-mail. O IP é usado somente para gerar a chave temporária do limite e não é persistido.

Administradores podem listar, exportar CSV e excluir registros em **Ferramentas > Interesses Casa Kotti**. A exportação neutraliza células iniciadas por caracteres de fórmula. O plugin também participa das ferramentas nativas em **Ferramentas > Exportar dados pessoais** e **Apagar dados pessoais**.

O trecho sugerido pelo plugin para a política é apenas um ponto factual sobre os dados técnicos coletados. Ele deve ser revisado e incorporado à política jurídica aprovada; o projeto não inventa informações jurídicas.

## Logo e folhagens

- **Logo integrado:** o símbolo e o lettering originais foram preservados em `assets/images/logo-casa-kotti.png`, com fundo transparente e proporções originais. O painel continua permitindo substituí-lo sem alterar o tema.
- **Folhagens integradas:** o SVG original fornecido foi sanitizado e separado nos dois grupos de ramo existentes:
  - `theme/casa-kotti/assets/images/foliage-left.svg`;
  - `theme/casa-kotti/assets/images/foliage-right.svg`.

O tema só renderiza esses assets empacotados e sanitizados; não habilita upload irrestrito de SVG. Cada ramo mantém seus paths e transforms originais e recebe uma oscilação independente a partir da base. A opção **Ativar animações das folhagens** permanece editável no Personalizador e `prefers-reduced-motion` sempre exibe a composição estática.

A composição pública usa os ramos originais como moldura lateral a partir dos cantos inferiores, no mesmo espírito do rótulo. O recorte das folhas fica na camada decorativa; a página pode rolar verticalmente em telas baixas ou com texto maior. O centro permanece reservado para logo, títulos e formulário.

Para gerar novamente os pacotes:

```bash
rm -f dist/casa-kotti.zip dist/casa-kotti-interesses.zip
(cd theme && zip -qr ../dist/casa-kotti.zip casa-kotti)
(cd plugin && zip -qr ../dist/casa-kotti-interesses.zip casa-kotti-interesses)
```

## Prévia local

A prévia estática ilustra a composição sem WordPress, persistência ou comportamento AJAX:

```bash
php -S 127.0.0.1:8080 -t preview
```

Acesse `http://127.0.0.1:8080`. Ela não substitui o teste do tema/plugin em WordPress.

Capturas da implementação executada em WordPress:

- [`preview/desktop.png`](preview/desktop.png) (1440 × 900)
- [`preview/mobile.png`](preview/mobile.png) (375 × 812)
- [`preview/viewport-320.png`](preview/viewport-320.png)
- [`preview/viewport-430.png`](preview/viewport-430.png)
- [`preview/viewport-768.png`](preview/viewport-768.png)

## Atualização GitHub → servidor

1. Revise e integre a alteração no GitHub.
2. Gere os ZIPs a partir da revisão aprovada ou use os artefatos de `dist/`.
3. Faça backup do banco e dos arquivos do site conforme o processo do servidor.
4. Em homologação, atualize primeiro o plugin e depois o tema; valide a página e um cadastro.
5. Repita a atualização em produção sem remover o plugin.

Atualizar ou trocar o tema não apaga os cadastros. O plugin deliberadamente preserva sua tabela ao ser desativado ou removido, evitando perda acidental. Textos e links ficam nas opções de tema; mantenha backup do banco e migre essas opções se o slug do tema for alterado.

## Compatibilidade

- WordPress 6.2 ou superior.
- PHP 7.4 ou superior.
- Sem page builder, bibliotecas JavaScript ou requisições externas.
- Montserrat variável local em WOFF2, acompanhada da licença SIL Open Font License.
- CSS e JavaScript carregados pelas APIs nativas do WordPress.
