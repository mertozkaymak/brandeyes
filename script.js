(($) => {

    let firstAccess = true;

    let $post = async () => {
        return $.post("https://dev.digitalfikirler.com/brandeyes/controller.php", { productId: pageParams.product.id, action: 1 });
    }
    
    let optionSlider = () => {
        if (window.location.href.indexOf("/urun") < 1) return false;
        $("head").append(`<style type="text/css">
            .product-left #product-secondary-image {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
            }
        </style>`);

        $(".product-right .product-options .product-options-content").before(`
            <div class="owl-carousel owl-theme" id="owl-product-options"></div>
        `);

        $(".product-left #product-primary-image").parent().append(`
            <div id="product-secondary-image">
                <img src="" alt=""></img>
            </div>
        `);
        $post().then(async response => {
            let find = false, images = new Array(), selectedVariant = false;
            response = JSON.parse(response);
            for (const iterator of $(".product-right .product-options .variant-list-group[data-group-id='2'] span")) {
                find = false;
                response.forEach(element => {
                    if (element.name === $(iterator).attr("data-option-title").trim()){
                        find = element;
                    }
                });
                if (find !== false) images.push(`<img src="https://www.brandeyes.com.tr/myassets/products/${ find.image }" alt="${ find.name }"></img>`);
                if ($(iterator).hasClass("variant-selected") !== false) selectedVariant = $(iterator).attr("data-option-title").trim();
            }
            for (let index = 0; index < images.length; index++) {
                $(".product-right .product-options #owl-product-options").append(`
                    <div class="item">
                        ${ images[index] }
                    </div>
                `);
            }
            if(selectedVariant !== false){
                $(".product-right .product-options #owl-product-options .item img").each(function(){
                    if($(this).attr("alt") == selectedVariant){
                        $(this).parent().addClass("selected");
                    }
                });
            }
            $('.product-right .product-options #owl-product-options').owlCarousel({
                loop: false,
                margin: 10,
                nav: true,
                responsive:{
                    0:{
                        items:4
                    }
                },
                onInitialized: function(){

                    $(".product-right .product-options #owl-product-options .owl-nav").removeClass("disabled");
                    $(".product-right .product-options #owl-product-options .owl-nav .owl-prev span").html(`<i class="fas fa-arrow-left"></i>`);
                    $(".product-right .product-options #owl-product-options .owl-nav .owl-next span").html(`<i class="fas fa-arrow-right"></i>`);

                    $("head").append(`<style type="text/css">

                        .product-right .product-options #owl-product-options{
                            width: 100%;
                            display: flex;
                            margin-bottom: 3rem;
                        }
                        .product-right .product-options #owl-product-options .owl-nav .owl-prev,
                        .product-right .product-options #owl-product-options .owl-nav .owl-next{
                            position: absolute;
                            font-size: 1.6rem;
                            height: 100%;
                            background: rgb(0, 0, 0, 0.5);
                            border-radius: 10px;
                            width: 35px;
                            color: #FFF;
                        }
                        .owl-prev.disabled, .owl-next.disabled{
                            display: none !important;
                        }
                        .product-right .product-options #owl-product-options .owl-nav .owl-prev{
                            top: 0;
                            left: -2rem;
                        }
                        .product-right .product-options #owl-product-options .owl-nav .owl-next{
                            top: 0;
                            right: -2rem;
                        }
                        .product-right .product-options #owl-product-options .item{
                            padding: .5rem;
                            width: 85px;
                            height: 85px;
                            display: flex;
                            align-items: center;
                        }
                        .product-right .product-options #owl-product-options .item.selected{
                            border: solid 1px;
                            color: gainsboro;
                        }
                        .product-right .product-options #owl-product-options .item:hover{
                            cursor: pointer;
                            border: solid 1px;
                            color: gainsboro;
                        }
                        .owl-carousel .owl-stage{
                            display: flex;
                            align-items: center;
                        }
                        @media (max-width: 768px){
                            .product-right .product-options #owl-product-options .owl-nav{
                                display: none !important;
                            }
                        }

                    </style>`);

                }

            });
            if(firstAccess !== false){
                firstAccess = false;
                $(document).on("click", ".product-right .product-options #owl-product-options .item", function(){

                    let targetOption, targetOption2 = false;
                    targetOption = $(this).find("img").attr("alt");
                    $(this).parents(".product-options").find(".variant-list-group[data-group-id='2'] span").each(function(){
                        if($(this).attr("data-option-title").trim() == targetOption){
                            targetOption2 = $(this);
                        }
                    });
                    if(targetOption2 !== false) targetOption2.trigger("click");
                });
            }
        });
    }

    $(document).ready(() => optionSlider());
    $(document).on("DOMNodeRemoved", ".loading-bar", () => optionSlider());

})(jQuery);