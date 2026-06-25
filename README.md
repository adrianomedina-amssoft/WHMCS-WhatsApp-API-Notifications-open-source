# WHMCS WhatsApp API Notifications — AMS Soft Fork

> Módulo addon WHMCS para envio automatizado de notificações via WhatsApp e Chatwoot.

**Mantido por [AMS Soft](https://www.amssoft.com.br)** — Adriano Medina

---

## 📸 Screenshots

### Notificações
![Notificação](screenshots/1%20notifica%C3%A7%C3%A3o.png)
![Detalhe Notificação](screenshots/1-1%20notifica%C3%A7%C3%A3o.png)

### Relatórios
![Relatórios](screenshots/2%20relatorios.png)

### Campanhas WhatsApp
![Campanhas](screenshots/3%20campanhas%20whatsApp.png)
![Campanhas Detalhe](screenshots/3-1%20campanhas%20whatsApp.png)
![Campanhas Envio](screenshots/3-2%20campanhas%20whatsApp.png)

---

## 🚀 Diferenciais deste Fork

Este fork contém melhorias e correções em relação ao projeto original:

### Correções de Bugs

- **v4.6.1** — Fix race condition em `processBulk()` que causava disparo duplicado de mensagens
- **v4.6.0** — Fix race condition em `startRecurringRun()` com `atomicSetInProgress()`
- Correção de seleção de template Meta WhatsApp na edição de campanha
- Correção de travamento de campanha recorrente ao cancelar disparo em andamento
- Correção de parâmetro de query malformado ao buscar templates Meta WhatsApp

### Funcionalidades

- **Campanhas recorrentes** — Envio automático diário, semanal ou mensal
- **Múltiplas plataformas** — WhatsApp (Meta API), Chatwoot, Evolution API, Baileys
- **Fila de envio** — Processamento em lotes com concorrência configurável
- **Relatórios** — Trilha de auditoria completa de todas as notificações enviadas
- **Multi-idioma** — Português (BR/PT) e Inglês

### Melhorias Técnicas

- Lock de processamento baseado em timestamp para prevenir execução concorrente
- Autoload PSR-4 para classes do módulo
- Migrações versionadas de banco de dados
- Análise estática com PHPStan e PHP_CodeSniffer

---

## 📦 Instalação

### Pré-requisitos

- PHP 8.1+
- WHMCS 8.x+
- Composer

### Passos

1. Clone o repositório:
   ```bash
   git clone https://github.com/adrianomedina-amssoft/WHMCS-WhatsApp-API-Notifications-open-source.git
   ```

2. Copie a pasta `modules/addons/lknhooknotification/` para o diretório de addons do WHMCS

3. Instale dependências:
   ```bash
   cd /path/to/whmcs/modules/addons/lknhooknotification
   composer install
   ```

4. Ative o módulo em WHMCS Admin → Addon Modules → Lkn Hook Notification → Activate

5. Configure as plataformas de notificação desejadas

---

## 🔧 Desenvolvimento

### Análise estática

```bash
./vendor/bin/phpstan analyse src/
./vendor/bin/phpcs --standard=PSR12 src/
./vendor/bin/phan --no-progress-bar
```

### Estrutura do Projeto

```
lknhooknotification/
├── src/Core/                    # 8 bounded contexts (DDD)
│   ├── AdminUI/                 # Dashboard administrativo
│   ├── BulkMessaging/           # Campanhas em massa
│   ├── Notification/            # Sistema de notificações
│   ├── NotificationQueue/       # Fila de entrega
│   ├── NotificationReport/      # Trilha de auditoria
│   ├── Platforms/               # Integrações (WhatsApp, Chatwoot, etc.)
│   ├── Shared/                  # Cross-cutting concerns
│   └── WHMCS/                   # Integração WHMCS
└── src/Notifications/           # 34 notificações customizadas
```

---

## 📄 Licença

Consulte o arquivo [EULA.txt](modules/addons/lknhooknotification/EULA.txt) para termos de uso.

---

## 🤝 Suporte

- **AMS Soft**: [amssoft.com.br](https://www.amssoft.com.br)
- **E-mail**: contato@amssoft.com.br
- **GitHub**: [adrianomedina-amssoft](https://github.com/adrianomedina-amssoft)
