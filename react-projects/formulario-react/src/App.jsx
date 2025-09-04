import React from "react";
import "./App.css";

function App() {
  return (
    <div className="card">
      <h2>Formulario de Contacto</h2>
      <form className="form">
        <div className="form-group">
          <label htmlFor="name">Nombre</label>
          <input type="text" id="name" placeholder="Ingresa tu nombre" />
        </div>
        <div className="form-group">
          <label htmlFor="email">Correo</label>
          <input type="email" id="email" placeholder="Ingresa tu correo" />
        </div>
        <div className="form-group">
          <label htmlFor="message">Mensaje</label>
          <textarea id="message" placeholder="Escribe tu mensaje"></textarea>
        </div>
        <button type="submit" className="btn-submit">Enviar</button>
      </form>
    </div>
  );
}

export default App;
