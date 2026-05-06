import { useState } from "react";
import "./Header.css";

export const Header = () => {
  const [menuOpen, setMenuOpen] = useState(false);

  return (
    <header className="header">
      <div className="logo">MyPortfolio</div>

      <nav className={menuOpen ? "nav active" : "nav"}>
        <a href="#">Home</a>
        <a href="#aboutmee">About</a>
        <a href="#skills">  Skills</a>
        <a href="#projects">Projects</a>
        <a href="#contact">Contact</a>
      </nav>

      <div className="menu-icon" onClick={() => setMenuOpen(!menuOpen)}>
        ☰
      </div>
    </header>
  );
};
