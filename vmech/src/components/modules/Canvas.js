export default class Canvas {
    constructor(options) {
        options = (options instanceof Object) ? options : {};
        if (options.id) {
            this.canvas = document.getElementById(options.id);
        } else {
            //this.canvas = document.createElement('canvas');
            //document.getElementsByClassName('game')[0].appendChild(this.canvas);
        }
        if(this.canvas){
            this.context = this.canvas.getContext('2d');
            //Настройки размера окна
            this.canvas.width = options.width || 2000;
            this.canvas.height = options.height || 1000;
        }
    }

    //Очистить экран
    clear() {
        this.context.clearRect(0, 0, this.canvas.width, this.canvas.height);
        //this.context.fillStyle = '#eeeeee';
        //this.context.fillRect(0, 0, this.canvas.width, this.canvas.height);
    }

    //нарисовать изображение
    drawImage(img, x, y) {
        this.context.drawImage(img, x, y);
    }

    drawImageFromSpriteMap(img, x, y, width, height, spriteMap, name, direction = null){
        if(direction){
            let angle = 0;
            let dx = 0;
            let dy = 0;
            switch(direction) {
                case 'up':
                    angle = 0;
                    dx = x;
                    dy = y;
                    break;
                case 'down':
                    angle = Math.PI;
                    dx = x + width;
                    dy = y + height;
                    break;
                case 'left':
                    angle = -Math.PI/2;
                    dx = x;
                    dy = y + height;
                    break;
                case 'right':
                    angle = Math.PI/2;
                    dx = x + width;
                    dy = y;
                    break;
                default: 
                    break;
            }
            this.context.translate(dx, dy);
            this.context.rotate(angle);
            this.context.drawImage(img, spriteMap[name].x, spriteMap[name].y, spriteMap[name].width, spriteMap[name].height, 0, 0, width, height);
            this.context.rotate(-angle);
            this.context.translate(-dx, -dy);
            return;
        }
        this.context.drawImage(img, spriteMap[name].x, spriteMap[name].y, spriteMap[name].width, spriteMap[name].height, x, y, width, height);
    }

    drawImageScale(img, x, y, width, height, direction, sx = 0, sy = 0, swidth = img.width, sheight = img.height) {
        if(direction){
            let angle = 0;
            let dx = 0;
            let dy = 0;
            switch(direction) {
                case 'up':
                    angle = 0;
                    dx = x;
                    dy = y;
                    break;
                case 'down':
                    angle = Math.PI;
                    dx = x + width;
                    dy = y + height;
                    break;
                case 'left':
                    angle = -Math.PI/2;
                    dx = x;
                    dy = y + height;
                    break;
                case 'right':
                    angle = Math.PI/2;
                    dx = x + width;
                    dy = y;
                    break;
                default: 
                    break;
            }
            this.context.translate(dx, dy);
            this.context.rotate(angle);
            this.context.drawImage(img, sx, sy, swidth, sheight, 0, 0, width, height);
            this.context.rotate(-angle);
            this.context.translate(-dx, -dy);
            return;
        }
        this.context.drawImage(img, sx, sy, swidth, sheight, x, y, width, height);
    }

    drawText(text, x, y, color) {
        this.context.fillStyle = color;
        this.context.font = "15px serif";
        this.context.fillText(text, x, y, 50);
    }

    drawRect(x, y, width, height, color) {
        this.context.fillStyle = color;
        this.context.fillRect(x, y, width, height);
    }
}