import "bootstrap";
import * as $ from 'jquery' ;
import './file_manager';


const $app = {
    initListenners : function (){
        document.getElementById('drop_zone').addEventListener('dragover', function(ev) {
            ev.preventDefault();
        });

        document.getElementById('drop_zone').addEventListener('drop', function(ev) {
            ev.preventDefault();
            if (ev.dataTransfer.items) {
                [...ev.dataTransfer.items].forEach((item, i) => {
                    if (item.kind === "file") {
                        const file = item.getAsFile();
                        document.querySelector('.file-name').innerHTML = file.name;
                        document.querySelector('.file input').files = ev.dataTransfer.files;
                    }
                });
            }
        });

        document.getElementById('drop_zone').addEventListener('click', function(e){
            let inputFile =document.querySelector('.file input');
            inputFile.click();
            inputFile.addEventListener('change', function(e){
                document.querySelector('.file-name').innerHTML = inputFile.files[0].name;
            })
        })
    },

    handleForm : function(){
        document.querySelector('.btn-form').addEventListener('click', function (e){
            e.preventDefault();
            document.querySelector('.loader').style.opacity = "1";
            document.querySelector('.loader').style.zIndex = "1";
            let url = document.querySelector('.form-content').dataset.url;
            let data = new FormData(document.querySelector('form[name=yaml_file]'));

            let divMess = document.querySelector('.div-message');
            divMess.style.display = 'none';
            divMess.innerHTML = "";

            $.ajax({
                url: url,
                cache: false,
                data: data,
                method: 'POST',
                processData: false,
                contentType: false,
                success : function (json){

                    // document.querySelector('.loader').style.opacity = 0;
                    // let form = document.createElement('form');
                    // form.action = document.querySelector('.loader').dataset.dl;
                    // form.hidden = true;
                    // form.method = "POST";
                    //
                    // let input = document.createElement('input');
                    // input.value = json.file;
                    // input.name = 'file';
                    // form.appendChild(input);
                    // document.querySelector('body').appendChild(form);
                    form.submit();

                },
                error : function (json){
                    // let message = document.createElement('p');
                    // let divMess = document.querySelector('.div-message');
                    // json = json.responseJSON;
                    // divMess.innerHTML = "";
                    // message.innerHTML = json.message;
                    // divMess.appendChild(message)
                    // divMess.style.display = 'block';
                    // document.querySelector('.loader').style.opacity = 0;
                    // document.querySelector('.loader').style.zIndex = -1;
                },
            })
        })
    },

    init : function (){
        this.initListenners();
        this.handleForm();
    }
}

window.addEventListener('load', function (){
    $app.init();
})





