# Pedidos compartilhados de serviço

O fluxo de pedidos compartilhados atende as categorias empresariais Banho e Tosa, Hotel para Pets, Creche Pet e Adestramento. Somente empresas ativas, com pagamento ativo e plano vigente recebem pedidos.

## Rodadas de notificação

- Empresas com plano em destaque recebem o pedido imediatamente.
- Se nenhuma empresa enviar orçamento em 30 minutos, o processo avisa as demais empresas elegíveis.
- Se não houver empresa em destaque, todas as empresas elegíveis recebem o pedido imediatamente.
- Enviar orçamento interrompe a expansão pendente. Uma recusa não conta como contato com orçamento.
- O tutor só vê preço depois que uma empresa enviar uma proposta.

## Instalação

1. Aplicar `database/migration_027_pedidos_servico_compartilhados.sql` no banco da aplicação.
2. No Agendador de Tarefas do Windows, criar uma tarefa repetida a cada 5 minutos.
3. Configurar a tarefa para iniciar `scripts/processar_pedidos_servico.bat`.
4. Se o XAMPP estiver instalado em outro local, ajustar o caminho do PHP em `scripts/processar_pedidos_servico.bat`.

A execução manual para conferir o processo pode ser feita chamando o arquivo `.bat`. O script PHP recusa chamadas via navegador.
