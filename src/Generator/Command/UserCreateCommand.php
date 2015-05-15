<?php
/*
 *  (c) Rogério Adriano da Silva <rogerioadris.silva@gmail.com>
 */

namespace Crud\Generator\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Security\Core\User\User;

/**
 * Class UserCreateCommand
 */
class UserCreateCommand extends AbstractCommand
{
    /**
     * configure
     */
    protected function configure()
    {
        $this
            ->setName('crud:user:create')
            ->setDescription('Adicionar um novo usuário')
            ->addOption('no-password', null, InputOption::VALUE_NONE, 'Não validar força da senha.');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getHelper('dialog');

        // Capturar nome
        $name = $dialog->askAndValidate($output, '<comment>Nome:</comment> ', function ($value) {
            if ('' === trim($value) || strlen(trim($value)) < 5) {
                throw new \Exception('Preencha o nome completo, o nome deve ter no mínimo 5 caracteres.');
            }

            return $value;
        });

        // Capturar nome de usuário
        $username = $dialog->askAndValidate($output, '<comment>Nome de usuário:</comment> ', function ($value) {
            if ('' === trim($value) || strlen(trim($value)) < 5) {
                throw new \Exception('Preencha o nome de usuário, o nome de usuário deve ter no mínimo 5 caracteres.');
            }

            return $value;
        });

        // Capturar email
        $email = $dialog->askAndValidate($output, '<comment>E-mail:</comment> ', function ($value) {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('Preencha o e-mail, informe um e-mail válido.');
            }

            return $value;
        });

        // Capturar senha
        $password = $dialog->askHiddenResponseAndValidate($output, '<comment>Senha:</comment> ', function ($value) use ($input) {
            if ('' === trim($value) || strlen(trim($value)) < 3) {
                throw new \Exception('Preencha a senha, sua senha deve ter no mínimo 3 caracteres.');
            }

            if (!$input->getOption('no-password') && $this->testPassword($value) < 15) {
                throw new \Exception('Crie uma senha mais forte tente combinar numeros e caracteres especiais.');
            }

            return $value;
        });

        // Criar novo usuário
        $user = new User($username, $password);
        $encoder = $this->get('security.encoder_factory')->getEncoder($user);
        $password = $encoder->encodePassword($user->getPassword(), $user->getSalt());

        $dataAtual = new \DateTime();

        try {
            $update_query = "INSERT INTO `users` (`username`, `password`, `email`, `name`, `created`, `updated`) VALUES (?, ?, ?, ?, ?, ?)";
            $this->get('db')->executeUpdate($update_query, array($username, $password, $email, $name, $dataAtual->format('Y-m-d H:i:s'), $dataAtual->format('Y-m-d H:i:s')));

            $output->writeln('<fg=green>Usuário criado com sucesso</fg=green>');
        } catch (\Exception $e) {
            $output->writeln(sprintf('<fg=red>Não foi possível criar o usuário: "%s"</fg=red>', $e->getMessage()));
        }
    }

    private function testPassword($password)
    {
        if (strlen($password) == 0) {
            return 0;
        }

        $strength = 0;

        $length = strlen($password);

        /**
         * Verificar se a senha não é toda minúscula
         */
        if (strtolower($password) != $password) {
            $strength++;
        }

        /**
         * Verificar se a senha não é toda maiúscula
         */
        if (strtoupper($password) == $password) {
            $strength++;
        }

        /**
         * Verificar se a senha tem mais de 5
         */
        if ($length >= 10) {
            $strength++;
        }

        /**
         * Verificar se a senha tem mais de 10 caracteres
         */
        if ($length >= 15) {
            $strength++;
        }

        /**
         * Verificar se a senha tem mais de 15 caracteres
         */
        if ($length >= 20) {
            $strength++;
        }

        /**
         * Verificar se a senha tem caracteres
         */
        preg_match_all('/[A-z]/', $password, $chars);
        $strength += count($chars[0]);

        /**
         * Verificar se a senha tem numero
         */
        preg_match_all('/[0-9]/', $password, $number);
        $strength += count($number[0]);

        if (count($chars[0]) > 0 && count($number[0])) {
            $strength += ((count($chars[0]) + count($number[0])) / 2);
        }

        /**
         * Verificar se a senha tem caracteres especiais
         */
        preg_match_all("/[|!@#$%&*\/=?,;.:\-_+~^\\\]/", $password, $specialchars);
        $strength += count($specialchars[0]);

        /**
         * Verificar quantos caracteres não repetidos existe
         */
        $strength += count(array_unique(str_split($password)));

        return (int) ceil($strength);
    }
}
