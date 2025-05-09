# CEP API - Laravel

Este é um projeto Laravel que fornece uma API para consulta de CEPs e gerenciamento de favoritos por usuários autenticados. Ele consulta um serviço externo de CEP (como ViaCEP), armazena os resultados em cache e permite aos usuários adicionar apelidos e gerenciar seus CEPs favoritos.

---

## 🧰 Tecnologias

- PHP 8+
- Laravel 10
- SQLite (ou outro banco suportado)
- PHPUnit 10 (para testes)
- Laravel Sanctum (para autenticação)

---

## 🚀 Instalação

Clone o repositório e instale as dependências:

```bash
git clone https://github.com/justgu1/cepapi.git
cd cepapi
composer install
cp .env.example .env
php artisan key:generate
```

---

## 🚀 Configurações

Configure a variável de ambiente com a URL da API de CEP:
CEP_API_URI=https://viacep.com.br

Crie e migre o banco de dados:
php artisan migrate

## 🧪 Testes
Execute os testes de feature e unitários:

```bash
php artisan test
```

---

## 📋 Endpoints da API

Você pode ver todos os endpoints detalhados com swagger:
GET /api/documentation

Consultar um CEP:
GET /api/cep/{cep}

Consulta um CEP no banco ou, caso não exista, via API externa.

Adicionar CEP aos favoritos:
POST /api/favorite/{cep}

Requer autenticação. 
Payload:

```json
{
  "nickname": "Casa da vovó"
}
```

Listar favoritos do usuário:
GET /api/my-list

Requer autenticação.

---

## 🔒 Autenticação
 A autenticação é feita com Laravel Sanctum. Gere um token de login para acessar endpoints protegidos:
 
 ```bash
 php artisan tinker
 >>> $user = \App\Models\User::factory()->create();
 >>> $user->createToken('API Token')->plainTextToken;
 ```
 
 Use o token no cabeçalho Authorization:
 Authorization: Bearer {token}

---

## 🧼 Qualidade do Código
 Código limpo: Seguimos as convenções do PSR-12 e utilizamos atributos #[Test] para testes.
 Testes robustos: Todas as interações externas são mockadas com Http::fake.
 Cache: Resultados de CEPs são armazenados em cache para reduzir chamadas externas.
 Documentação: Endpoints documentados com exemplos claros.

---

## 📞 Contato
 Para dúvidas ou sugestões, abra uma issue ou entre em contato com szguisantos@gmail.com ou +55 (11) 97659-8853.
