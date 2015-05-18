<?php
/*
 *  (c) Rogério Adriano da Silva <rogerioadris.silva@gmail.com>
 */

namespace Crud\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Filesystem\Filesystem;
use Crud\Helper\CamelCaseHelper;

/**
 * Class GeneratorCommand
 */
class GeneratorCommand extends AbstractCommand
{
    /**
     * configure
     */
    protected function configure()
    {
        $this
            ->setName('crud:generator')
            ->setDescription('Gerar arquivos a partir de um banco de dados')
            ->addOption('tables', null, InputOption::VALUE_REQUIRED, 'define tables generator');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Busca todas tabelas do banco
        $getTables = array_map(function ($value) { return array_values($value)[0]; }, $this->get('db')->fetchAll('SHOW TABLES', array()));

        // Remove a tabela de usuário da lista
        $getTables = array_filter($getTables, function ($campo) {
            return $campo !== 'users';
        });

        if (count($getTables) === 0) {
            return $output->writeln('<error>Nenhuma tabela foi encontrada.</error>');
        }

        if ($input->getOption('tables') === null) {
            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion('Selecione as tabelas para gerar os padrões CRUD <comment>(pressione em branco para selecionar todas)</comment>', $getTables, implode(',', array_keys($getTables)));
            $question->setMultiselect(true);
            $tables_generate = $helper->ask($input, $output, $question);
        } else {
            $tables_in = explode(',', $input->getOption('tables'));
            $tables_generate = array();
            foreach ($tables_in as $table_in) {
                if (in_array($table_in, $getTables)) {
                    $tables_generate[] = $table_in;
                }
            }
        }

        if (count($tables_generate) === 0) {
            return $output->writeln('<error>Nenhuma tabela foi selecionada.</error>');
        }

        $output->writeln('Você selecionou: <comment>'.implode('</comment>, <comment>', $tables_generate).'</comment>');

        $tables = array();
        foreach ($tables_generate as $table_name) {
            if ($output->isVerbose()) {
                $output->writeln(sprintf('Capturando informações sobre a tabela <comment>"%s"</comment>', $table_name));
            }
            $table_info = $this->getInfoTable($table_name, $input, $output);
            if (is_array($table_info)) {
                $tables[$table_name] = $table_info;
            } else {
                if ($output->isVerbose()) {
                    $output->writeln(sprintf('<info>A tabela "%s" não será gerada.</info>', $table_name));
                }
            }
        }
        $output->writeln('Aguarde estamos gerando...');

        foreach ($tables as $table_name => $data) {
            $this->createController($table_name, $data, $input, $output);
            $this->createViews($table_name, $data, $input, $output);
            $this->createRoutes($table_name, $data, $input, $output);
            $this->createMenu($table_name, $data, $input, $output);
        }
    }

    /**
     * Retorna informações sobre a tablela
     *
     * @param  string $table_name
     * @return array
     */
    private function getInfoTable($table_name, InputInterface $input, OutputInterface $output)
    {
        $table_column = array();
        $table_form = array();

        $table_result = $this->get('db')->fetchAll(sprintf('DESC `%s`', $table_name), array());

        $primary_key = null;
        $primary_keys = 0;
        $primary_keys_auto = 0;

        array_map(function ($column) use (&$primary_keys, &$primary_keys_auto) {
                if ($column['Key'] === 'PRI') {
                    $primary_keys++;
                }
                if ($column['Extra'] == 'auto_increment') {
                    $primary_keys_auto++;
                }
            }, $table_result);

        if (!($primary_keys === 1 || ($primary_keys > 1 && $primary_keys_auto === 1))) {
            return;
        }

        foreach ($table_result as $column) {
            if ((($primary_keys > 1 && $primary_keys_auto == 1) and ($column['Extra'] == 'auto_increment')) or ($column['Key'] == "PRI")) {
                $primary_key = $column['Field'];
            }

            $table_result_column = array(
                    'name' => $column['Field'],
                    'title' => ucfirst($column['Field']),
                    'primary' => $column['Field'] == $primary_key ? true : false,
                    'nullable' => $column['Null'] == 'NO' ? true : false,
                    'auto' => $column['Extra'] == 'auto_increment' ? true : false,
                    'type' => preg_replace('/\((\d+)\)$/', '', $column['Type']),
                    'lenght' => (int) preg_replace('/[^\d+]/', '', $column['Type']),
                );

            if (!in_array(strtolower($column['Field']), array('id', 'created', 'updated'))) {
                switch ($table_result_column['type']) {
                        case 'text':
                        case 'tinytext':
                        case 'mediumtext':
                        case 'longtext':
                            $type_form = 'textarea';
                            $regex = '';
                            break;

                        case 'datetime':
                            $type_form = 'text';
                            $regex = '';
                            break;

                        default:
                            $type_form = 'text';
                            $regex = '';
                            break;
                    }

                $table_form[] = array_merge($table_result_column, array(
                        'type' => $type_form,
                        'validation_regex' => $regex,
                    ));
            }
            $table_column[] = $table_result_column;
        }

        return array(
            'primary_key' => $primary_key,
            'columns' => $table_column,
            'columns_form' => $table_form,
        );
    }

    /**
     * Gerar controller
     *
     * @param  string          $table_name
     * @param  array           $data
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     */
    private function createController($table_name, array $data, InputInterface $input, OutputInterface $output)
    {
        if ($output->isVerbose()) {
            $output->writeln(sprintf('Gerando controller da tabela <comment>"%s"</comment>', $table_name));
        }
        $fs = new Filesystem();
        $dir_controller = realpath(__DIR__.'/../Controller');

        $table_camel = CamelCaseHelper::encode($table_name, true);
        $file_controller = sprintf('%s/%sController.php', $dir_controller, $table_camel);

        if (is_file($file_controller)) {

        }

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('O arquivo controller já existe deseja subistituir? <info>(y ou n)</info>: ');

        if (!$helper->ask($input, $output, $question)) {
            $output->writeln(sprintf('O arquivo <comment>"%s"</comment> não foi alterado.', $file_controller));
            return;
        }

        $controller = $this->get('twig')->render('generator/controller.twig', array('table' => $table_name, 'data' => $data, 'table_camel' => $table_camel));
        $fs->dumpFile($file_controller, $controller);
    }

    /**
     * Gerar views
     *
     * @param  string          $table_name
     * @param  array           $data
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     */
    private function createViews($table_name, array $data, InputInterface $input, OutputInterface $output)
    {
        $fs = new Filesystem();
        $dir_views = realpath(__DIR__.'/../../views/');
        $dir_view = sprintf('%s/%s', $dir_views, $table_name);

        if ($fs->exists($dir_view) === false) {
            $fs->mkdir($dir_view, 0755);
        }

        $list_view = $this->get('twig')->render('generator/theme.twig', array('table' => $table_name, 'data' => $data));
        $fs->dumpFile(sprintf('%s/theme.twig', $dir_view), $list_view);

        $list_view = $this->get('twig')->render('generator/list.twig', array('table' => $table_name, 'data' => $data));
        $fs->dumpFile(sprintf('%s/list.twig', $dir_view), $list_view);

        $list_view = $this->get('twig')->render('generator/create.twig', array('table' => $table_name, 'data' => $data));
        $fs->dumpFile(sprintf('%s/create.twig', $dir_view), $list_view);

        $list_view = $this->get('twig')->render('generator/edit.twig', array('table' => $table_name, 'data' => $data));
        $fs->dumpFile(sprintf('%s/edit.twig', $dir_view), $list_view);
    }

    /**
     * Adicionar rotas no arquivo de rotas
     *
     * @param  string          $table_name
     * @param  array           $data
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     */
    public function createRoutes($table_name, array $data, InputInterface $input, OutputInterface $output)
    {
        $fs = new Filesystem();
        $file_routes = __DIR__.'/../routes.php';
        if ($fs->exists($file_routes)) {
            $file_contents = array_map(function ($line) { return preg_replace('/\n/', '', $line); }, file($file_routes));
            $table_routes = array();
            $exists = array(
                'index' => false,
                'list' => false,
                'create' => false,
                'edit' => false,
                'delete' => false,
            );
            $table_lower = strtolower($table_name);
            $table_camel = CamelCaseHelper::encode($table_name, true);
            foreach (array_keys($exists) as $route) {
                $lines_found = array_keys(preg_grep(sprintf('/\'%s::%s\'/i', $table_camel, $route), $file_contents));
                $exists[$route] = count($lines_found) === 1;
            }
            if ($exists['index'] === false) {
                $table_routes[] = "\$route->get(sprintf('/%s/{$table_lower}', \$app['security_path']), '{$table_camel}::index')->bind('{$table_lower}');";
            }
            if ($exists['list'] === false) {
                $table_routes[] = "\$route->get(sprintf('/%s/{$table_lower}/list', \$app['security_path']), '{$table_camel}::list')->bind('{$table_lower}_list');";
            }
            if ($exists['create'] === false) {
                $table_routes[] = "\$route->match(sprintf('/%s/{$table_lower}/create', \$app['security_path']), '{$table_camel}::create')->method('GET|POST')->bind('{$table_lower}_create');";
            }
            if ($exists['edit'] === false) {
                $table_routes[] = "\$route->match(sprintf('/%s/{$table_lower}/edit/{id}', \$app['security_path']), '{$table_camel}::edit')->method('GET|POST')->bind('{$table_lower}_edit');";
            }
            if ($exists['delete'] === false) {
                $table_routes[] = "\$route->get(sprintf('/%s/{$table_lower}/delete/{id}', \$app['security_path']), '{$table_camel}::delete')->bind('{$table_lower}_delete');";
            }
            $last_line = array_keys(preg_grep('/return/', $file_contents))[0];
            // Rewriting
            $rewriting = array();
            $line_blank = 0;
            foreach ($file_contents as $line => $value) {
                // Add routes
                if (count($table_routes) > 0 && $last_line == $line) {
                    $rewriting[] = '// '.$table_camel;
                    foreach ($table_routes as $route_value) {
                        $rewriting[] = $route_value;
                    }
                    $rewriting[] = '';
                }
                if (strlen(trim($value)) === 0) {
                    $line_blank++;
                } else {
                    $line_blank = 0;
                }
                if ($line_blank <= 1) {
                    $rewriting[] = $value;
                }
            }
            $fs->dumpFile($file_routes, implode("\n", $rewriting));
        }
    }

    /**
     * Adicionar link no menu
     *
     * @param  string          $table_name
     * @param  array           $data
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     */
    public function createMenu($table_name, array $data, InputInterface $input, OutputInterface $output)
    {
        $fs = new Filesystem();
        $file_menus = __DIR__.'/../../views/menu.twig';
        if ($fs->exists($file_menus)) {
            $file_contents = array_map(function ($line) { return preg_replace('/\n/', '', $line); }, file($file_menus));
            $table_lower = strtolower($table_name);
            $table_upper = ucfirst($table_name);
            if (!preg_grep(sprintf('/\{\{([ ]*)path\(([ ]*)\'%s\'([ ]*)\)/', strtolower($table_lower)), $file_contents)) {
                $file_contents[] = '<li {% if menu_selected is defined and menu_selected == \''.$table_lower.'\' %}class="active"{% endif %}>';
                $file_contents[] = "\t<a href=\"{{ path('{$table_lower}') }}\">";
                $file_contents[] = "\t\t<i class=\"fa fa-bars\"></i> <span>{$table_upper}</span>";
                $file_contents[] = "\t</a>";
                $file_contents[] = '</li>';
            }
            $fs->dumpFile($file_menus, implode("\n", $file_contents));
        }
    }
}
