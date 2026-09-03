const { test, expect } = require('@playwright/test');

const required = ['E2E_EMAIL', 'E2E_PASSWORD', 'E2E_PET_ID', 'E2E_PRODUTO_ID'];
for (const name of required) {
    if (!process.env[name]) {
        throw new Error(`Variável obrigatória ausente para E2E: ${name}`);
    }
}

async function entrar(page) {
    await page.goto('login.php');
    await page.getByLabel('E-mail').fill(process.env.E2E_EMAIL);
    await page.getByLabel('Senha').fill(process.env.E2E_PASSWORD);
    await page.getByRole('button', { name: /Entrar/i }).click();
    await expect(page).toHaveURL(/dashboard|onboarding|2fa/);
}

test.describe('fluxos críticos reais', () => {
    test('login e solicitação de adoção', async ({ page }) => {
        await entrar(page);
        await page.goto(`solicitar_adocao.php?pet_id=${process.env.E2E_PET_ID}`);
        await expect(page.getByRole('heading', { name: /Adotar/i })).toBeVisible();
        await page.getByLabel(/Conte um pouco/i).fill('Teste E2E: ambiente de homologação.');
        await page.getByRole('button', { name: /Enviar Solicitação/i }).click();
        await expect(page).toHaveURL(/minhas_solicitacoes\.php\?enviado=1/);
    });

    test('compra completa até confirmação', async ({ page }) => {
        await entrar(page);
        await page.goto(`produto.php?id=${process.env.E2E_PRODUTO_ID}`);
        await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
        await page.getByRole('button', { name: /Adicionar ao Carrinho/i }).click();
        await page.goto('checkout.php');
        await expect(page.getByRole('heading', { name: /Finalizar Compra/i })).toBeVisible();
        await page.getByRole('button', { name: /Confirmar Pedido|Finalizar Compra|Comprar/i }).click();
        await expect(page).toHaveURL(/pedido_confirmado\.php/);
        await expect(page.getByText(/Pedido confirmado/i)).toBeVisible();
    });
});
