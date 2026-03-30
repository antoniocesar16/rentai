# Sistema de Agendamento de Apartamentos via WhatsApp

## 📋 Visão Geral

Sistema que permite proprietários divulgarem apartamentos através do WhatsApp de forma automatizada, onde administradores controlam as instâncias do WhatsApp e locadores cadastram seus imóveis.

---

## 🎯 Conceito do Negócio

O sistema opera em uma estrutura hierárquica de três níveis:

1. **Administrador** → Cria e gerencia instâncias do WhatsApp
2. **Locador/Proprietário** → Cadastra apartamentos em uma instância atribuída
3. **Cliente Final** → Interage via WhatsApp para ver imóveis e agendar visitas

---

## 🔑 Regras de Negócio Principais

### **RN01 - Hierarquia de Controle**
- [ ] Apenas administradores podem criar instâncias do WhatsApp
- [ ] Cada instância representa um número de WhatsApp conectado
- [ ] Locadores não têm acesso à criação/configuração de instâncias

### **RN02 - Vinculação de Locadores**
- [ ] Um locador deve estar vinculado a uma instância específica
- [ ] Um locador só pode cadastrar apartamentos na instância atribuída a ele
- [ ] Um locador não visualiza apartamentos de outros locadores

### **RN03 - Gestão de Instâncias**
- [ ] Cada instância é criada via Evolution API
- [ ] A conexão é feita através de QR Code escaneado pelo admin
- [ ] Status possíveis: `conectada`, `desconectada`, `inativa`
- [ ] Admin pode ativar/desativar instâncias a qualquer momento

### **RN04 - Cadastro de Apartamentos**
- [ ] Locador acessa sistema web (Filament) com suas credenciais
- [ ] Cadastra informações completas do imóvel
- [ ] Faz upload de múltiplas fotos
- [ ] Define status de disponibilidade

### **RN05 - Automação WhatsApp**
- [ ] Quando cliente envia mensagem, bot responde automaticamente
- [ ] Bot lista todos os apartamentos disponíveis da instância
- [ ] Sistema envia fotos e informações de cada imóvel
- [ ] Registra interesse do cliente como lead

### **RN06 - Isolamento de Dados**
- [ ] Admin visualiza todas as instâncias e todos os apartamentos
- [ ] Locador visualiza apenas seus próprios apartamentos
- [ ] Cliente vê apenas apartamentos da instância que contatou

### **RN07 - Disponibilidade**
- [ ] Apartamento pode estar `disponível` ou `indisponível`
- [ ] Apenas apartamentos disponíveis são enviados pelo bot
- [ ] Locador pode alterar status a qualquer momento

---

## 👥 Perfis de Usuário

### **Administrador**

**Pode:**
- [] Criar novas instâncias WhatsApp
- [] Gerar e visualizar QR Code para conexão
- [] Monitorar status de todas as instâncias
- [] Cadastrar novos locadores
- [] Vincular/desvincular locadores de instâncias
- [] Visualizar todos os apartamentos do sistema
- [] Acessar relatórios e métricas globais
- [] Desativar instâncias ou locadores

**Não pode:**
- [ ] Cadastrar apartamentos em nome do locador

---

### **Locador/Proprietário**

**Pode:**
- [] Fazer login no sistema web
- [] Cadastrar apartamentos
- [] Upload de fotos dos imóveis
- [] Editar informações de seus apartamentos
- [] Ativar/desativar disponibilidade
- [] Visualizar leads recebidos
- [] Ver agendamentos de visitas

**Não pode:**
- [ ] Criar ou configurar instâncias WhatsApp
- [ ] Visualizar apartamentos de outros locadores
- [ ] Alterar configurações da instância
- [ ] Cadastrar outros locadores

---

### **Cliente (via WhatsApp)**

**Pode:**
- [] Enviar mensagem para o WhatsApp
- [] Receber lista de apartamentos disponíveis
- [] Ver fotos e detalhes dos imóveis
- [] Solicitar agendamento de visita
- [] Fazer perguntas ao bot

**Não pode:**
- [ ] Acessar sistema web
- [ ] Ver apartamentos de outras instâncias

---

## 🔄 Fluxo de Negócio

### **Fluxo 1: Configuração Inicial (Admin)**

- [ ] 1. Admin acessa painel administrativo
- [ ] 2. Clica em "Nova Instância WhatsApp"
- [ ] 3. Informa nome da instância (ex: "Imóveis Centro SP")
- [ ] 4. Sistema chama Evolution API e cria instância
- [ ] 5. Sistema exibe QR Code na tela
- [ ] 6. Admin escaneia QR Code com WhatsApp
- [ ] 7. Instância fica com status "conectada"
- [ ] 8. Admin cadastra novo locador (ou seleciona existente)
- [ ] 9. Admin vincula locador à instância criada

---

### **Fluxo 2: Cadastro de Imóvel (Locador)**

- [ ] 1. Locador faz login no sistema Filament
- [ ] 2. Acessa menu "Meus Apartamentos"
- [ ] 3. Clica em "Novo Apartamento"
- [ ] 4. Preenche formulário:
  - [ ] Título do anúncio
  - [ ] Endereço completo
  - [ ] Número de quartos
  - [ ] Número de banheiros
  - [ ] Área em m²
  - [ ] Valor do aluguel
  - [ ] Descrição detalhada
  - [ ] Aceita pets (sim/não)
  - [ ] Vagas de garagem
- [ ] 5. Faz upload de 3 a 10 fotos
- [ ] 6. Define status como "Disponível"
- [ ] 7. Clica em "Salvar"
- [ ] 8. Sistema registra apartamento vinculado à instância do locador

---

### **Fluxo 3: Interação do Cliente via WhatsApp**

- [ ] 1. Cliente envia mensagem "Oi" para o número WhatsApp
- [ ] 2. Sistema recebe mensagem via webhook da Evolution API
- [ ] 3. Sistema identifica a instância pelo número
- [ ] 4. Sistema busca todos os apartamentos disponíveis dessa instância
- [ ] 5. Bot responde automaticamente:
  - [ ] Mensagem de boas-vindas
  - [ ] Lista resumida de apartamentos (título + preço + quartos)
- [ ] 6. Cliente responde com número do apartamento
- [ ] 7. Bot envia:
  - [ ] Todas as fotos do apartamento
  - [ ] Informações completas
  - [ ] Opções: "Agendar visita" ou "Mais informações"
- [ ] 8. Cliente solicita agendamento
- [ ] 9. Sistema registra lead no banco de dados
- [ ] 10. Sistema notifica locador sobre novo interesse

---

## 📊 Entidades Principais

### **Instância WhatsApp**
- [ ] Nome da instância
- [ ] Chave da Evolution API
- [ ] Número do telefone
- [ ] Status da conexão
- [ ] Data de criação
- [ ] Administrador responsável

### **Locador**
- [ ] Nome completo
- [ ] Email
- [ ] Telefone
- [ ] CPF/CNPJ
- [ ] Instância vinculada (apenas uma)
- [ ] Data de cadastro

### **Apartamento**
- [ ] Locador responsável
- [ ] Título do anúncio
- [ ] Endereço completo
- [ ] Quartos
- [ ] Banheiros
- [ ] Área (m²)
- [ ] Valor do aluguel
- [ ] Descrição
- [ ] Aceita pets
- [ ] Vagas de garagem
- [ ] Status (disponível/indisponível)
- [ ] Galeria de fotos

### **Lead/Interesse**
- [ ] Apartamento de interesse
- [ ] Nome do cliente
- [ ] Telefone do cliente
- [ ] Mensagem inicial
- [ ] Data/hora do contato
- [ ] Status (novo, em contato, agendado, finalizado)

---

## 🔐 Regras de Segurança

### **RS01 - Autenticação**
- [ ] Sistema usa guards separados: `admin` e `locador`
- [ ] Cada guard tem sessões e permissões isoladas

### **RS02 - Isolamento de Dados**
- [ ] Locador não acessa dados de outros locadores
- [ ] Queries devem sempre filtrar por `locador_id` ou `instancia_id`

### **RS03 - Validação de Propriedade**
- [ ] Ao editar apartamento, sistema valida se pertence ao locador logado
- [ ] Ao criar apartamento, sistema vincula automaticamente ao locador

### **RS04 - Acesso às Instâncias**
- [ ] Apenas admin pode criar/editar/deletar instâncias
- [ ] Locador tem acesso read-only à sua instância

---

## 🤖 Lógica do Bot Automatizado

### **Regra BOT01 - Detecção de Intenção**
```
Mensagem recebida → Sistema analisa palavras-chave:
```
- [ ] "oi", "olá", "bom dia" → Mensagem de boas-vindas + listar imóveis
- [ ] "agendar", "visita", "ver" → Processo de agendamento
- [ ] "preço", "valor" → Filtrar por faixa de preço
- [ ] "quartos" → Filtrar por número de quartos

### **Regra BOT02 - Formato de Resposta**
```
Para cada apartamento disponível:
```
- [ ] 1. Enviar primeira foto como destaque
- [ ] 2. Enviar texto formatado:
  - [ ] 🏠 [Título]
  - [ ] 💰 R$ [Valor]
  - [ ] 📍 [Endereço resumido]
  - [ ] 🛏️ [Quartos] quartos | 🚿 [Banheiros] banheiros
- [ ] 3. Oferecer opções de interação

### **Regra BOT03 - Registro de Interação**
```
Toda mensagem recebida gera registro:
```
- [ ] Data/hora
- [ ] Número do remetente
- [ ] Mensagem enviada
- [ ] Resposta do bot
- [ ] Apartamento(s) enviado(s)

---

## 📈 Métricas e Indicadores

### **Para Admin:**
- [ ] Total de instâncias ativas
- [ ] Total de locadores cadastrados
- [ ] Total de apartamentos disponíveis
- [ ] Leads gerados por instância (últimos 30 dias)
- [ ] Taxa de resposta do bot

### **Para Locador:**
- [ ] Quantidade de apartamentos cadastrados
- [ ] Apartamentos disponíveis vs indisponíveis
- [ ] Leads recebidos (últimos 7/30 dias)
- [ ] Visualizações por apartamento
- [ ] Agendamentos confirmados

---

## 🚫 Restrições do Sistema

### **RE01 - Limite de Instâncias**
- [ ] Número de instâncias definido pelo admin
- [ ] Sem limite técnico, apenas gerencial

### **RE02 - Limite de Fotos**
- [ ] Mínimo: 3 fotos por apartamento
- [ ] Máximo: 10 fotos por apartamento
- [ ] Formatos aceitos: JPG, PNG, WebP
- [ ] Tamanho máximo por foto: 5MB

### **RE03 - Responsabilidade de Conteúdo**
- [ ] Locador é responsável pelas informações cadastradas
- [ ] Sistema não valida veracidade dos dados
- [ ] Admin pode moderar conteúdo inadequado

### **RE04 - Conexão WhatsApp**
- [ ] Instância desconectada não envia mensagens
- [ ] Sistema alerta admin sobre instâncias offline
- [ ] Mensagens recebidas durante desconexão são perdidas

---

## ✅ Casos de Uso Principais

### **UC01 - Admin Cria Instância**
**Ator:** Administrador  
**Fluxo:**
- [ ] 1. Admin acessa "Instâncias WhatsApp"
- [ ] 2. Clica em "Criar Nova"
- [ ] 3. Preenche nome da instância
- [ ] 4. Sistema gera QR Code via Evolution API
- [ ] 5. Admin escaneia QR Code
- [ ] 6. Sistema confirma conexão
- [ ] 7. Instância fica disponível para uso

---

### **UC02 - Admin Vincula Locador**
**Ator:** Administrador  
**Fluxo:**
- [ ] 1. Admin acessa "Locadores"
- [ ] 2. Cria novo locador (ou seleciona existente)
- [ ] 3. Seleciona instância WhatsApp
- [ ] 4. Salva vinculação
- [ ] 5. Locador recebe credenciais de acesso

---

### **UC03 - Locador Cadastra Apartamento**
**Ator:** Locador  
**Fluxo:**
- [ ] 1. Locador faz login
- [ ] 2. Acessa "Apartamentos"
- [ ] 3. Clica em "Novo"
- [ ] 4. Preenche formulário completo
- [ ] 5. Faz upload de fotos
- [ ] 6. Salva como "Disponível"
- [ ] 7. Sistema vincula à instância do locador

---

### **UC04 - Cliente Solicita Informações**
**Ator:** Cliente via WhatsApp  
**Fluxo:**
- [ ] 1. Cliente envia mensagem
- [ ] 2. Bot responde com lista de imóveis
- [ ] 3. Cliente escolhe apartamento
- [ ] 4. Bot envia detalhes completos + fotos
- [ ] 5. Cliente solicita agendamento
- [ ] 6. Sistema registra lead
- [ ] 7. Sistema notifica locador

---

### **UC05 - Locador Altera Disponibilidade**
**Ator:** Locador  
**Fluxo:**
- [ ] 1. Locador acessa apartamento
- [ ] 2. Muda status para "Indisponível"
- [ ] 3. Sistema para de enviar via bot
- [ ] 4. Apartamento some da lista automática

---

## 🎯 Objetivos do Sistema

- [ ] 1. **Automatizar** divulgação de imóveis via WhatsApp
- [ ] 2. **Centralizar** gerenciamento em painel web
- [ ] 3. **Facilitar** cadastro para locadores
- [ ] 4. **Agilizar** atendimento a clientes
- [ ] 5. **Organizar** leads e agendamentos
- [ ] 6. **Escalar** operação com múltiplas instâncias

---

## 📝 Observações Importantes

- [ ] Sistema depende 100% da Evolution API para funcionar
- [ ] Instância desconectada interrompe automação
- [ ] Locador deve manter informações atualizadas
- [ ] Bot não substitui atendimento humano, apenas filtra interesse inicial
- [ ] Dados sensíveis (CPF, telefone) devem ser criptografados
- [ ] Sistema deve ter backup regular das fotos

---

**Versão:** 1.0  
**Última atualização:** 2026-02-11