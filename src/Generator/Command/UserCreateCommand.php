<?php
/*
 *  (c) Rogério Adriano da Silva <rogerioadris.silva@gmail.com>
 */

namespace Crud\Generator\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
            ->setDescription('Adicionar um novo usuário');
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
        $name = $dialog->askAndValidate($output, '<fg=yellow>Nome:</fg=yellow> ', function ($value) {
            if ('' === trim($value) || strlen(trim($value)) < 3) {
                throw new \Exception('Informe o nome.');
            }

            return $value;
        });

        // Capturar nome de usuário
        $username = $dialog->askAndValidate($output, '<fg=yellow>Nome de usuário:</fg=yellow> ', function ($value) {
            if ('' === trim($value) || strlen(trim($value)) < 3) {
                throw new \Exception('Informe o nome de usuário.');
            }

            return $value;
        });

        // Capturar email
        $email = $dialog->askAndValidate($output, '<fg=yellow>E-mail:</fg=yellow> ', function ($value) {
            if ('' === trim($value) || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('Informe um e-mail válido.');
            }

            return $value;
        });

        // Capturar senha
        $password = $dialog->askHiddenResponseAndValidate($output, '<fg=yellow>Senha:</fg=yellow> ', function ($value) {
            if ('' === trim($value) || strlen(trim($value)) < 6) {
                throw new \Exception('Informe a senha, sua senha deve ter no mínimo 6 caracteres.');
            }

            return $value;
        });

        // Criar novo usuário
        $user = new \Symfony\Component\Security\Core\User\User($username, $password);
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
}
