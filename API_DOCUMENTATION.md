# Documentação da API - Clínica

## Autenticação

A API usa autenticação por token Bearer. Primeiro você precisa fazer login para obter o token.

### Fazer Login

**POST** `/api/login`

```json
{
    "email": "admin@clinica.com",
    "password": "password123"
}
```

**Resposta de sucesso:**
```json
{
    "message": "Login realizado com sucesso",
    "user": {
        "id": 1,
        "name": "Admin User",
        "email": "admin@clinica.com",
        "email_verified_at": null,
        "created_at": "2025-07-25T03:47:14.000000Z",
        "updated_at": "2025-07-25T03:47:14.000000Z"
    },
    "token": "1|abcdef123456...",
    "token_type": "Bearer"
}
```

### Registrar Novo Usuário

**POST** `/api/register`

```json
{
    "name": "João Silva",
    "email": "joao@exemplo.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

### Resposta para Acesso Não Autorizado

Quando um usuário tentar acessar qualquer rota protegida sem fornecer um token válido, a API retornará:

```json
{
    "message": "Token de acesso não fornecido ou inválido.",
    "error": "Unauthenticated",
    "status": 401,
    "success": false
}
```

**Status HTTP:** 401 Unauthorized

### Endpoints de Teste

**GET** `/api/test-public` - Rota pública (não requer autenticação)
**GET** `/api/test-protected` - Rota protegida (requer token)

### Usar Token nas Requisições

Para todas as rotas protegidas, inclua o token no header:

```
Authorization: Bearer 1|abcdef123456...
```

### Rotas Protegidas

Todas as rotas abaixo precisam do token de autenticação:

- **GET** `/api/me` - Informações do usuário autenticado
- **POST** `/api/logout` - Logout (revoga token atual)
- **POST** `/api/logout-all` - Logout de todos os dispositivos

#### Profissionais
- **GET** `/api/profissionais` - Listar profissionais
- **POST** `/api/profissionais` - Criar profissional
- **GET** `/api/profissionais/{id}` - Visualizar profissional
- **PUT** `/api/profissionais/{id}` - Atualizar profissional
- **DELETE** `/api/profissionais/{id}` - Excluir profissional

#### Pacientes
- **GET** `/api/pacientes` - Listar pacientes
- **POST** `/api/pacientes` - Criar paciente
- **GET** `/api/pacientes/{id}` - Visualizar paciente
- **PUT** `/api/pacientes/{id}` - Atualizar paciente
- **DELETE** `/api/pacientes/{id}` - Excluir paciente

#### Usuários
- **GET** `/api/usuarios` - Listar usuários
- **POST** `/api/usuarios` - Criar usuário
- **GET** `/api/usuarios/{id}` - Visualizar usuário
- **PUT** `/api/usuarios/{id}` - Atualizar usuário
- **DELETE** `/api/usuarios/{id}` - Excluir usuário
- **PUT** `/api/usuarios/{id}/change-password` - Alterar senha
- **PUT** `/api/usuarios/{id}/reset-password` - Resetar senha
- **GET** `/api/usuarios-statistics` - Estatísticas de usuários
- **GET** `/api/usuarios-tipos` - Tipos de usuários

#### Financeiros
- **GET** `/api/financeiros` - Listar registros financeiros
- **POST** `/api/financeiros` - Criar registro financeiro
- **GET** `/api/financeiros/{id}` - Visualizar registro financeiro
- **PUT** `/api/financeiros/{id}` - Atualizar registro financeiro
- **DELETE** `/api/financeiros/{id}` - Excluir registro financeiro
- **GET** `/api/financeiros-resumo-mensal/{mes}` - Resumo mensal
- **GET** `/api/financeiros-resumo-anual/{ano}` - Resumo anual
- **GET** `/api/financeiros-meses-disponiveis` - Meses disponíveis
- **GET** `/api/financeiros-relatorio-completo/{mes}` - Relatório completo

#### Agendamentos
- **GET** `/api/agendamentos` - Listar agendamentos
- **POST** `/api/agendamentos` - Criar agendamento
- **GET** `/api/agendamentos/{id}` - Visualizar agendamento
- **PUT** `/api/agendamentos/{id}` - Atualizar agendamento
- **DELETE** `/api/agendamentos/{id}` - Excluir agendamento
- **PUT** `/api/agendamentos/{id}/confirmar` - Confirmar agendamento
- **GET** `/api/agendamentos-estatisticas` - Estatísticas de agendamentos
- **GET** `/api/agendamentos-dias-semana` - Dias da semana disponíveis

#### Atendimentos
- **GET** `/api/atendimentos` - Listar atendimentos
- **POST** `/api/atendimentos` - Criar atendimento
- **GET** `/api/atendimentos/{id}` - Visualizar atendimento
- **PUT** `/api/atendimentos/{id}` - Atualizar atendimento
- **DELETE** `/api/atendimentos/{id}` - Excluir atendimento
- **PUT** `/api/atendimentos/{id}/confirmar` - Confirmar atendimento
- **PUT** `/api/atendimentos/{id}/cancelar` - Cancelar atendimento
- **POST** `/api/atendimentos/agendamento/{agendamento_id}` - Criar atendimento a partir de agendamento
- **GET** `/api/atendimentos-estatisticas` - Estatísticas de atendimentos
- **GET** `/api/atendimentos-relatorio-profissional/{profissional_id}` - Relatório por profissional
- **GET** `/api/atendimentos-relatorio-completo` - Relatório completo
- **POST** `/api/atendimentos-processar-atrasados` - Processar atendimentos atrasados

## Exemplos de Teste com cURL

### 1. Fazer Login
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@clinica.com",
    "password": "password123"
  }'
```

### 2. Testar rota protegida (substitua SEU_TOKEN pelo token recebido no login)
```bash
curl -X GET http://localhost:8000/api/me \
  -H "Authorization: Bearer SEU_TOKEN"
```

### 3. Listar profissionais
```bash
curl -X GET http://localhost:8000/api/profissionais \
  -H "Authorization: Bearer SEU_TOKEN"
```

### 4. Logout
```bash
curl -X POST http://localhost:8000/api/logout \
  -H "Authorization: Bearer SEU_TOKEN"
```
