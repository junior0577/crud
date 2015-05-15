CRUD admin
===================

O que é CRUD admin ?
-----------------------------
- **O CRUD Admin**  é uma ferramenta para gerar um **backend completo a partir de um banco de dados MySql** onde você pode criar, ler, atualizar e excluir registros em um banco de dados.

- **O backend é gerado em segundos** sem arquivos de configuração, onde há um monte de *"mágica"* e é muito difícil de se adaptar às suas necessidades.

- **O código gerado é totalmente personalizável e extensível.**

O CRUD admin tem sido desenvolvido em cima do micro framework [Silex](http://silex.sensiolabs.org).



Instalação
------------

Clone o repositório

    git clone https://github.com/LuzPropria-360/crud

    cd crud

Use o composer para instalar as dependência

    php composer.phar install

Configure a conexão com o banco de dados no arquivo **DoctrineServiceProvider** em `src/Provider/DoctrineServiceProvider.php`

```php
    $app['dbs.options'] = array(
        'db' => array(
            'driver'   => 'pdo_mysql',
            'dbname'   => 'DATABASE_NAME',
            'host'     => '127.0.0.1',
            'user'     => 'DATABASE_USER',
            'password' => 'DATABASE_PASS',
            'charset'  => 'utf8',
        ),
    );
```

Agora, execute o comando que irá gerar o backend CRUD:

    php console crud:generator

Inicie o servidor:

    php -S 0.0.0.0:8080 -t web/ web/index.php

Abra em seu navegador:

    http://localhost:8080/security

