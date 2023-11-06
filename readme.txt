=== Pix para Woocommerce – OpenPix ===
Contributors: openpix
Tags: woocommerce, openpix, payment
Requires at least: 4.0
Tested up to: 6.1.1
Requires PHP: 5.6
Stable tag: 2.10.5
License: GPLv2 or later
License URI: <http://www.gnu.org/licenses/gpl-2.0.html>

Receba pagamentos via Pix usando a OpenPix

== Description ==

O Plugin Pix para Woocommerce – OpenPix permite que seus clientes realizem pagamentos utilizando o Pix dentro da sua loja virtual, 24 horas por dia, 7 dias por semana, via QR code ou “copiar e colar".

É prático, rápido, seguro para o cliente e para a sua loja. **A confirmação do pagamento é realizada em tempo real**, o que aumenta as conversões do seu ecommerce e reduz o custo com boletos e crédito à vista.

## **VANTAGENS DO PLUGIN PIX PARA WOOCOMMERCE - OPENPIX**

* Atualização em tempo real após pagamento
* Mais conversão
* Depósitos via Pix no seu CNPJ
* Integração em 1 clique
* WebHook: Aviso de recebimento em tempo real
* Mais autonomia para cobranças e pagamentos
* Suporte via Chat
* Transações ilimitadas
* Sem mensalidades
* Livre de burocracia
* Envio via WhatsApp, Email e SMS
* Geração QrCode Pix em Tempo Real
* Pague somente por Pix recebido
* Cobrança Recorrente e assinaturas
* Pix parcelado
* Anti-fraude nativo sem custos adicionais
* Painel para acompanhamento de todas as transações e conciliação bancária

## **COMECE JÁ A ACEITAR PIX NO SEU WOOCOMMERCE E OFEREÇA A OPÇÃO DE PAGAMENTO MAIS PRÁTICA DO MERCADO**

1 - Instale o plugin Pix Woocommerce - OpenPix em seu site WordPress
2 - Crie uma conta na OpenPix [Criar conta](https://openpix.com.br/register?tags=from-woocommerce)
3 - Copie o ID de Cliente em sua conta OpenPix e clique em “Integrar com um clique”

Pronto! Após esses passos sua loja virtual pode aceitar pagamentos pelo Pix

Se precisar de ajuda você pode falar com nosso suporte via chat ou acessa a [documentação.](https://developers.openpix.com.br/docs/ecommerce/woocommerce-plugin)

## **PERGUNTAS FREQUENTES**

**O PLUGIN POSSUI CUSTO OU MENSALIDADE?**

Não, o plugin é 100% grátis, você só paga um percentual de **0,8% por Pix recebido.**

**QUAL A CONFIGURAÇÃO NECESSÁRIA PARA UTILIZAR O PLUGIN?**

- Ter instalado o WordPress 4.0 ou superior;
- Ter instalado o plugin WooCommerce 3.0 ou superior;
- Utilizar a versão 5.6 ou maior do PHP;
- Ter uma conta ativa na OpenPix

**POSSO UTILIZAR O PLUGIN PIX JUNTO COM OUTROS GATEWAYS DE PAGAMENTO?**

Sim, o plugin Pix para Woocommerce - OpenPix pode ser utilizado junto com outros gateways complementares como processadores de cartão, boleto, etc.

**ONDE AS TRANSAÇÕES RECEBIDAS SÃO DEPOSITADAS?**

As transações são depositadas diretamente na chave Pix veiculada ao seu CNPJ todos os dias. No painel OpenPix, na opção “Contas” você pode personalizar as configurações de depósito.

**COMO CONFIRMAR O PAGAMENTO PELO PIX?**

O Plugin informa automaticamente o Woocommerce o status do pagamento, mas caso você precise verificar e confirmar uma transação específica basta acessar o seu painel OpenPix na opção “Transações”.

Dentro da plataforma você encontrará todas as transações geradas e poderá ver todos os detalhes, realizar reembolsos e muito mais.

**SUPORTE**

Para questões relacionadas a integração e plugin, acesse o [Portal do Desenvolver OpenPix](https://developers.openpix.com.br/)

Se precisar falar com nosso time, acesse o chat disponível em nosso site.

== Screenshots ==

1. Exemplo de configuração do Plugin
2. Exemplo da Ordem de Pagamento com o QRCode Pix
3. Exemplo da Ordem de Pagamento com Giftback Aplicado
4. Exemplo da Ordem de Pagamento paga e com Giftback Ganho
5. Exemplo da Ordem de Pagamento Expirada

== Changelog ==

= 2.10.5 - 2023-11-06 =

- Adicionar melhor suporte ao modo legacy de armazenamento de pedidos nos métodos de pagamento Woovi Parcelado e Pix Crediário.

= 2.10.4 - 2023-11-06 =

- Adicionar melhor suporte ao modo legacy de armazenamento de pedidos.

= 2.10.3 - 2023-11-03 =

- Adicionar a variável `:orderId` na funcionalidade de redirect do WooCommerce.

= 2.10.2 - 2023-10-31 =

- Inicializar somente uma vez o plugin.

= 2.10.1 - 2023-10-26 =

- Adicionada compatibilidade com HPOS.
- Mostrar QRCode quando a página da minha conta tiver uma URL que não esteja em inglês.
- Remover a duplicação de gateways de pagamento na página minha conta.

= 2.10.0 - 2023-10-19 =

* Adicionar novo método de pagamento com Pix Crediário.

= 2.9.1 - 2023-10-17 =

* Adicionar novos parâmetros `order_id` e `key` na URL durante o redirecionamento quando uma cobrança for paga.

= 2.9.0 - 2023-10-09 =

* Permitir redirecionar o usuário quando uma cobrança for paga.

= 2.8.1 - 2023-09-14 =

* Melhoria na integração

= 2.8.0 - 2023-08-29 =

* Remoção Funcionalidade Giftback

= 2.7.1 - 2023-08-10 =

* Correção Assets

= 2.7.0 - 2023-08-10 =

* Correção de Imports

= 2.6.2 - 2023-07-19 =

* Correção de Classes da funcionalidade OpenPix Parcelado

= 2.6.1 - 2023-07-19 =

* Adição da funcionalidade OpenPix Parcelado

= 2.6.0 - 2023-05-02 =

* Adição da razão do reembolso nos pedidos

= 2.5.0 - 2023-04-27 =

* Adição de reembolsos de pedidos

= 2.4.0 – 2023-04-20 =

* Melhoria na configuração do plugin
* Melhoria no recebimento de Webhooks

= 2.3.0 – 2023-04-03 =

* Melhoria na exibição do plugin nos plugins NextMove e Elementor
* Melhoria na segurança para a aprovação de pedidos
* Melhoria na integração dos webhooks

= 2.2.0 – 2023-03-16 =

* Melhoria na exibição do plugin
* Melhoria na função de Copiar e Colar QrCode

= 2.1.9 – 2023-03-16 =

* Melhoria na exibição do plugin

= 2.1.8 - 2023-03-14 =

* Melhoria e otimização do Plugin de checkout
* Atualização de dependencias

= 2.1.7 - 2023-03-14 =

* Melhoria e otimização do Plugin de checkout
* Atualização de dependencias

= 2.1.6 - 2023-03-14 =

* Melhoria dos endpoints

= 2.1.5 - 2023-02-23 =

* Melhoria na descrição do plugin
* Melhoria na função de Copiar e Colar QrCode

= 2.1.4 - 2022-10-25 =

* Melhoria de erros

= 2.1.3 - 2022-06-08 =

* Melhoria de erros
* Melhoria ao aplicar cupons

= 2.1.2 - 2022-05-24 =

* Melhoria na integração do telefone
* Adição do QrCode na visualização do pedido
* Adição do link de pagamento na edição do pedido

= 2.1.1 - 2022-04-04 =

* Melhoria no cupom de Giftback
* Melhoria nos logs
* Adição de testes

= 2.1.0 - 2022-04-01 =

* Cupom Giftback
* Improved logs

= 2.0.3 - 2022-02-18 =

* Melhoria nos logs do Plugin

= 2.0.2 - 2022-02-01 =

* Melhoria nos eventos do Plugin

= 2.0.1 - 2022-02-01 =

* Novo UI de Ordem de Pagamento

= 2.0.0 - 2022-01-31 =

* Novo UI de Ordem de Pagamento

= 1.12.0 - 2021-11-09 =

* Melhoria no comentário Pix e imagens

= 1.11.0 - 2021-11-09 =

* Melhoria na atualização de pedido ao confirmar o pagamento via Pix

= 1.10.0 - 2021-11-08 =

* Melhoria na configuração automática do Webhook
* Número no pedido nas informaçõs adicionais

= 1.9.0 - 2021-10-29 =

* Configuração automática da integração do webhook

= 1.8.0 - 2021-09-23 =

* Permite atualizar a UI quando for recebido um pagamento

= 1.7.0 - 2021-09-21 =

* Permite customizar o status do pedido depois do Pix ser pago
* Melhoria no erro para o cliente final

= 1.6.1 - 2021-08-25 =

* Melhoria na validação de dados do pedido

= 1.6.0 - 2021-08-03 =

* Melhoria ao lidar com o CPF/CNPJ do cliente

= 1.5.0 - 2021-08-03 =

* Melhoria ao lidar com cliente de um pedido
* Para salvar o cliente recomendamos o uso do plugin [woocommerce-extra-checkout-fields-for-brazil](https://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/)

= 1.4.0 - 2021-07-12 =

* Melhoria no comentário Pix

= 1.3.0 - 2021-06-29 =

* Webhook/IPN mais robusto

= 1.2.0 - 2021-05-27 =

* Melhoria no Webhook/IPN

= 1.1.0 =

* Lógica de centavos mais robusta
* Permite customização no status da ordem baseado no status do Pix

= 1.0.1 =

* Melhorias no responsivo.

= 1.0.0 =

* Versão inical do plugin.
