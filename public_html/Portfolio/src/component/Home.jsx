import React from "react";
import "./Home.css";
import profile from "../assets/profile.jpg";
export const Home = () => {
  return (
    <section className="hero">

      {/* Navbar */}
      <header className="navbar">
        <h2 className="logo">Himanshu</h2>

        <nav>
          <a href="#">Home</a>
          <a href="#">About</a>
          <a href="#">Skills</a>
          <a href="#">Projects</a>
          <a href="#">Contact</a>
        </nav>
      </header>

      {/* Hero Content */}
      <div className="hero-content">

        <div className="left">
          <h1>Hi, I'm</h1>

      <h2 className="name">
  &lt;
  <span className="typing bounce">
    {"Himanshu".split("").map((letter, index) => (
      <span key={index} style={{ animationDelay: `${index * 0.1}s` }}>
        {letter}
      </span>
    ))}
  </span>
  &gt;
</h2>

          <h3>Frontend Developer | Creative Coder</h3>

          <p>
            I design and develop modern, responsive websites using
            HTML, CSS, JavaScript and React.
          </p>

          <p>
            I love turning ideas into real-world projects and solving problems through code.
          </p>

          <div className="buttons">
            <a href="#projects"><button className="btn">View Projects</button></a>
            <a href="#contact"><button className="btn outline">Hire Me</button></a>
          </div>
        </div>

        {/* ✅ RIGHT SIDE IMAGE */}
        <div className="right">
          <div className="img-box">
            <img src={profile} alt="profile" />
          </div>
        </div>

      </div>
    </section>
  );
};

 