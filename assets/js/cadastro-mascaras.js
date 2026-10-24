(function () {
    function soDigitos(v) { return (v || "").replace(/\D/g, ""); }

    // (31) 98765-4321 (celular) ou (31) 3456-7890 (fixo)
    function mascaraTelefone(v) {
        var d = soDigitos(v).slice(0, 11);
        if (d.length === 0) return "";
        if (d.length <= 2) return "(" + d;
        if (d.length <= 6) return "(" + d.slice(0, 2) + ") " + d.slice(2);
        if (d.length <= 10) return "(" + d.slice(0, 2) + ") " + d.slice(2, 6) + "-" + d.slice(6);
        return "(" + d.slice(0, 2) + ") " + d.slice(2, 7) + "-" + d.slice(7);
    }

    function mascaraCep(v) {
        var d = soDigitos(v).slice(0, 8);
        return d.length > 5 ? d.slice(0, 5) + "-" + d.slice(5) : d;
    }

    function ligar(id, fn) {
        var el = document.getElementById(id);
        if (!el) return null;
        el.addEventListener("input", function () { el.value = fn(el.value); });
        el.value = fn(el.value);
        return el;
    }

    function preencher(id, valor) {
        var el = document.getElementById(id);
        if (el && valor) el.value = valor;
    }

    document.addEventListener("DOMContentLoaded", function () {
        ligar("telefone", mascaraTelefone);
        var cep = ligar("cep", mascaraCep);
        if (!cep) return;

        cep.addEventListener("input", function () {
            if (soDigitos(cep.value).length !== 8) return;
            cep.disabled = true;
            fetch("https://viacep.com.br/ws/" + soDigitos(cep.value) + "/json/")
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (d.erro) { alert("CEP não encontrado."); return; }
                    preencher("logradouro", d.logradouro);
                    preencher("bairro", d.bairro);
                    preencher("cidade", d.localidade);
                    preencher("estado", d.uf);
                    var num = document.getElementById("numero");
                    if (num) num.focus();
                })
                .catch(function () { alert("Não foi possível buscar o CEP. Preencha manualmente."); })
                .finally(function () { cep.disabled = false; });
        });
    });
})();